<template>
  <v-card :class="getClassesVidget" :style="{ minWidth: minWidth + 'px' }">
    <v-row class="pt-2 pr-4" no-gutters>
      <v-col class="pr-4 pt-2" cols="auto">
        <v-row class="d-flex pl-4 mb-4 justify-start align-start" no-gutters>
          <v-col>
            <v-row class="d-flex justify-start mb-0">
              <span class="labelVidget ml-2">
                {{ title }}
                <slot name="title-right" />
              </span>
            </v-row>
          </v-col>
          <v-col v-if="periods" cols="auto" class="d-flex justify-end">
            <sv-toggle-button
              v-if="modePeriods.length > 0"
              ref="myToggleButton"
              v-model="localModePeriod"
              :items="modePeriods"
            ></sv-toggle-button>
          </v-col>
          <v-col v-else cols="auto" class="periodVidget">
            {{ period }}
          </v-col>
        </v-row>
      </v-col>
    </v-row>
    <v-row class="pt-0 pr-4" no-gutters>
      <v-col
        v-for="(counter, index) in counters"
        :key="index"
        :cols="getCols"
        class="d-flex flex-column justify-start"
      >
        <v-row no-gutters>
          <v-col cols="auto" class="titleVidget ml-4">{{ counter.label }}</v-col>
        </v-row>
        <v-row no-gutters>
          <v-col cols="auto" class="valueVidget ml-4">{{ counter.value }}</v-col>
        </v-row>
      </v-col>
    </v-row>
    <v-row class="pt-1 pr-4 pl-4 d-flex justify-start" no-gutters>
      <v-col class="d-flex justify-start" cols="12">
        <slot name="title-bottom"></slot>
      </v-col>
    </v-row>

    <v-row class="d-flex pl-4 pr-4 pb-4 justify-start" no-gutters>
      <v-col class="d-flex justify-start" cols="12">
        <template v-if="sortedItems.length === 0 && showErrorPanel">
          <sv-error-panel :text="$t('Dashboard.noError')"></sv-error-panel>
        </template>
        <template v-else="sortedItems.length">
          <data-table-hoc
            :height-offset="234"
            header-border
            :dense="dense"
            :table-name="tableName"
            :count="count"
            :headers="headers"
            :loading="loading"
            :items="sortedItems"
            :sort-by.sync="sortBy"
            :sort-desc.sync="sortDesc"
            :multi-sort="false"
            :show-filters="false"
            hide-default-footer
            :auto-height="autoHeight"
            :total-height="totalHeight"
            @fetch="sortTable"
          >
          </data-table-hoc>
        </template>
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
import { sortData, readLocalStorage, writeLocalStorage } from '@/utils/HelperFunctions';
import DataTableHoc from '@components/utils/DataTableHoc.vue';
import SvToggleButton from '@/components/from_ui_kit/SvToggleButton.vue';
import SvErrorPanel from './SvErrorPanel.vue';

export default {
  components: {
    DataTableHoc,
    SvToggleButton,
    SvErrorPanel,
  },
  props: {
    parameters: Object,
    title: {
      type: String,
      default: '',
    },
    period: {
      type: String,
      default: '',
    },
    tableName: {
      type: String,
      default: '',
    },
    // Показывать кнопки выбора периода
    periods: {
      type: Boolean,
      default: false,
    },
    modePeriods: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: false,
    },
    showErrorPanel: {
      type: Boolean,
      default: false,
    },
    headers: {
      type: Array,
      default: () => [],
    },
    items: {
      type: Array,
      default: () => [],
    },
    counters: {
      type: Array,
      default: () => [],
    },
    count: {
      type: Number,
      default: 15,
    },
    border: {
      type: Boolean,
      default: false,
    },
    shadow: {
      type: Boolean,
      default: false,
    },
    totalHeight: {
      type: Number,
      default: 300,
    },
    heightOffset: {
      type: Number,
      default: 132,
    },
    autoHeight: {
      type: Boolean,
      default: false,
    },
    dense: {
      type: Boolean,
      default: false,
    },
    modePeriod: {
      type: Number,
      default: 0,
    },
    autoReload: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      sortedItems: [],
      localModePeriod: null,
      sortBy: '',
      sortDesc: false,
      timerId: null,
      minWidth: 300,
    };
  },
  computed: {
    getClassesVidget() {
      return [this.border ? 'borderVidget' : '', this.shadow ? 'card-shadow' : 'card-shadow-none']
        .filter(Boolean)
        .join(' ');
    },

    getCols() {
      return this.counters.length > 0 ? Math.floor(12 / this.counters.length) : 12;
    },
  },
  watch: {
    localModePeriod(newVal) {
      this.changePeriod(newVal);
    },
    items: {
      handler(newItems) {
        this.sortedItems = [...newItems];
      },
      deep: true,
      immediate: true,
    },

    modePeriod: {
      handler(newVal) {
        if (this.localModePeriod !== newVal) {
          this.localModePeriod = newVal;
        }
      },
      immediate: true,
    },
  },
  mounted() {
    this.getDefSort();
    if (this.autoReload) this.startTimer();
  },
  beforeDestroy() {
    if (this.autoReload) this.clearTimer();
  },
  methods: {
    changePeriod(newPeriod) {
      this.$emit('refresh', newPeriod);

      if (this.autoReload) {
        this.clearTimer();
        this.startTimer();
      }
    },
    startTimer() {
      this.timerId = setInterval(() => {
        this.changePeriod(this.localModePeriod);
      }, 60000); // 60000 мс = 1 минута
    },
    clearTimer() {
      if (this.timerId) {
        clearInterval(this.timerId);
        this.timerId = null;
      }
    },
    sortTable(params) {
      //console.log('params', params);
      if (params?.sort?.length > 0) {
        const sortByPr = params.sort[0][0];
        const sortDirPr = params.sort[0][1];

        this.sortBy = sortByPr;
        this.sortDesc = sortDirPr === 'desc';

        const sortField = {
          field: this.sortBy,
          direction: sortDirPr,
        };

        this.sortedItems = sortData(this.sortedItems, [sortField]);

        writeLocalStorage(this.tableName + '_sort_by', this.sortBy);
        writeLocalStorage(this.tableName + '_sort_desc', this.sortDesc);
      }
    },

    getDefSort() {
      const sortByLs = String(readLocalStorage(this.tableName + '_sort_by'));
      const sortDescLs = String(readLocalStorage(this.tableName + '_sort_desc'));
      this.sortBy = sortByLs;
      this.sortDesc = sortDescLs === 'true' ? true : false;
    },
  },
};
</script>
<style lang="scss" scoped>
@import '@components/from_ui_kit/scss/branding.scss';

.periodVidget {
  font-size: 13px;
  font-weight: normal;
  color: var(--v-text-secondary-base);
}

.titleVidget {
  font-size: 14px;
  font-weight: normal;
  color: var(--v-text-secondary-base);
}

.valueVidget {
  font-size: 20px;
  color: var(--v-text-main-base);
  font-weight: 500;
}

.borderVidget {  
  border: 1px solid var(--v-border-main-base);
}

.card-shadow {
  // box-shadow: 0px 2px 16px 0px #3031331a, 0px 1px 2px 0px #30313314 !important;
}

.card-shadow-none {
  box-shadow: none !important;
}
</style>
