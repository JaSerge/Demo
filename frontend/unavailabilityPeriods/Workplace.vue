<template>
  <v-container fluid>
    <v-row no-gutters>
      <data-table-hoc
        ref="table-hoc"
        :height-offset="234"
        :filters.sync="filters"
        table-name="unavailability-periods_workplace"
        :schema="filterSchema"
        :headers="headers"
        :count="count"
        :loading="loading"
        :items="items"
        @fetch="loadUnavailabilityPeriods"
      >
        <template #before-filter>
          <v-btn
            v-if="hasPermission('add')"
            class="mr-3 v-size--default"
            color="primary"
            rounded
            small
            depressed
            default
            :to="{ name: 'UnavailabilityPeriods:create' }"
          >
            {{ $t('Common.create') }}
          </v-btn>
        </template>

        <template #item.is_unexpected="{ item }">          
            <sv-badge-adv
              outlined
              small
              :badge-class="getBgr(getClassColor(item.is_unexpected))"
              :text-color="getClassColor(item.is_unexpected)"
              :text="item.is_unexpected ? trans.trouble : trans.plan"
            >
            </sv-badge-adv>
        </template>

        <template #item.bts="{ item }">
          <div v-text="formatDate(item.bts)"></div>
        </template>

        <template #item.ets="{ item }">
          <div v-text="formatDate(item.ets)"></div>
        </template>

        <template #item.actions="{ item }">
          <sv-action-col>
            <sv-action-col-btn
              v-if="$canViewHistory()"
              :tooltip="trans.historyTooltip"
              @click="showHistory(item.id)"
            >
              mdi-history
            </sv-action-col-btn>

            <template v-if="$canEdit()">
              <sv-action-col-btn
                :disabled="item.is_closed === 1"
                :tooltip="trans.updateTooltip"
                :to="{ name: 'UnavailabilityPeriods:edit', params: { id: item.id } }"
              >
                mdi-pencil-outline
              </sv-action-col-btn>
            </template>

            <sv-action-col-btn
              v-if="$canDelete()"
              :disabled="item.is_closed === 1"
              :tooltip="trans.closeTooltip"
              @click="closeUnavailPeriod(item.id)"
            >
              mdi-close
            </sv-action-col-btn>
          </sv-action-col>
        </template>
      </data-table-hoc>
    </v-row>

    <show-history-modal v-model="historyDialog" table-name="operator_unavailability_periods" :item-id="selectedUnPeriodId" />

    <router-view       
      :operators="operators"
      @apply="emitFetch" 
    />

  </v-container>
</template>

<script>
import NotificationMixin from '@mixins/NotificationMixin';
import CheckPermissionMixin, { UnavailabilityPeriodsPermissionMixin } from '@mixins/CheckPermissionMixin';
import DialogMixin from '@mixins/DialogMixin';
import QueryMixin from '@mixins/QueryMixin';

import Repository from '@api/UnavailabilityPeriodsRepository';
import DataTableHoc from '@components/utils/DataTableHoc.vue';
import SvBadgeAdv from '@components/common/SvBadgeAdv.vue';
import ShowHistoryModal from '@/components/core/ShowHistoryModal';

import UnavailabilityPeriodsDialog from '@views/unavailabilityPeriods/CreateEditDialog.vue';
import { DateTime } from 'luxon';
import SelectFilter from '@components/utils/FiltersV2/SelectFilter.vue';
import SearchFilter from '@components/utils/FiltersV2/SearchFilter.vue';

export default {
  name: 'UnavailabilityPeriods',
  components: { UnavailabilityPeriodsDialog, DataTableHoc, ShowHistoryModal, SvBadgeAdv },
  mixins: [
    NotificationMixin,
    DialogMixin,
    QueryMixin,
    CheckPermissionMixin,
    UnavailabilityPeriodsPermissionMixin,
  ],
  data: () => ({    
    selectedUnPeriodId: null,
    count: 0,
    loading: false,    
    filterDialog: false,
    historyDialog: false,
    filters: {},
    items: [],
    operators: [],
    date: DateTime.local().setLocale('ru').toFormat('yyyy-MM-dd HH:mm:ss'),
    intervalId: '',    
  }),
  computed: {
    headers() {
      const { trans } = this;
      return [
        { text: trans.operator, value: 'nm' },
        { text: trans.description, value: 'dsc' },        
        { text: trans.bts, value: 'bts' },
        { text: trans.ets, value: 'ets' },
        { text: trans.work_type, value: 'is_unexpected' },
        { text: trans.actions, value: 'actions', sortable: false, align: 'center', width: '155px' },
      ];
    },
    filterSchema() {
      const { trans } = this;
      return {
        residentData: {
          title: trans.technicalWork,
          filters: {
            operators: {
              component: SelectFilter,
              title: trans.operator,
              filterBind: {
                items: this.operators,
                textItem: 'nm',
                valueItem: 'id',
              },
            },
            state: {
              component: SelectFilter,
              title: trans.state,
              filterBind: { items: this.states, valueItem: 'id', hideInverse: true },
            },            
            description: {
              component: SearchFilter,
              title: trans.description,
              filterBind: {},
            },
            isUnexpected: {
              component: SelectFilter,
              title: trans.trouble,
              filterBind: {
                items: this.is_unexpected,
                textItem: 'nm',
                valueItem: 'id',
              },
            },
          },
        },
      };
    },
    is_unexpected() {
      return [
        { id: true, nm: this.trans.trouble, value: true },
        { id: false, nm: this.trans.plan, value: false },
      ];
    },
    trans() {
      return {
        technicalWork: this.$t('UnavailabilityPeriods.technicalWork'),
        work_type: this.$t('UnavailabilityPeriods.fields.work_type'),
        operator: this.$t('UnavailabilityPeriods.fields.operator'),
        description: this.$t('UnavailabilityPeriods.fields.description'),
        bts: this.$t('UnavailabilityPeriods.fields.bts'),
        ets: this.$t('UnavailabilityPeriods.fields.ets'),        
        trouble: this.$t('UnavailabilityPeriods.work_types.trouble'),
        plan: this.$t('UnavailabilityPeriods.work_types.plan'),
        historyTooltip: this.$t('Common.actions.tooltips.history'),
        updateTooltip: this.$t('Common.edit'),
        closeTooltip: this.$t('Common.close'),
        state: this.$t('Common.fields.state'),
        trouble: this.$t('UnavailabilityPeriods.work_types.trouble'),
        plan: this.$t('UnavailabilityPeriods.work_types.plan'),
        closeTitle: this.$t('UnavailabilityPeriods.titles.close'),
        closeMes: this.$t('UnavailabilityPeriods.closeMes'),
        active: this.$t('UnavailabilityPeriods.states.active'),
        closed: this.$t('UnavailabilityPeriods.states.closed'),
        planned: this.$t('UnavailabilityPeriods.states.planned'),
        actions: this.$t('Common.fields.actions'),
      };
    },
    states() {
      return [
        { id: "closed", text: this.trans.closed },
        { id: "active", text: this.trans.active },
        { id: "planned", text: this.trans.planned }
      ];
    } 
  },
  async created() {
    this.intervalId = setInterval(() => this.updDate(), 1000);
    this.filters = this.initFilters();
    this.loadOperators();
  },
  methods: {
    updDate() {
      this.date = DateTime.local().setLocale('ru').toFormat('yyyy-MM-dd HH:mm:ss');
    },

    initFilters() {
      return {
        state: {
          items: ['active', 'planned'],
        }       
      };
    },

    formatDate(date) {
      if (date === null) return '';
      else return DateTime.fromSQL(date).toFormat('dd.MM.yyyy HH:mm:ss');
    },

    async loadOperators() {
      try {
        const { data } = await Repository.getAvailableOperators();
        this.operators = data.items;
      } catch (error) {
        this.sendError(error);
      }
    },

    async loadUnavailabilityPeriods(params) {
      try {
        this.loading = true;
        const { data } = await Repository.getUnavailabilityPeriods(params); 
        this.items = data.items;
        this.count = data.count;
        await this.loadOperators();
      } finally {
        this.loading = false;
      }
    },

    async closeUnavailPeriod(id) { 
        if (
          !(await this.confirm(this.trans.closeTitle, this.trans.closeMes, {}))
        )
          return;
      
          const { data } = await Repository.closeUnavailabilityPeriod(id);          
            
          this.sendAlert(data.alert);
          this.emitFetch();
      },    

    emitFetch() {
      this.$refs['table-hoc'].fetchData();
    },

    showHistory(id) {
      this.selectedUnPeriodId = id;
      this.historyDialog = true;
    },

    getClassColor(is_unexpected) {
      return is_unexpected ? 'error' : '';
    },

    getBgr (class_color) {
      let res = 'border: 1px solid ' + class_color + ' ';
      switch (class_color) {
        case 'success':
          res += 'chipSuccessBg';
          break;
        case 'error':
          res += 'chipErrorBg';
          break;
      }; 
      return res;
    },
  },
};
</script>

<style scoped lang="scss"></style>
