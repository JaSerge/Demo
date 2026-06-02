<template>
  <v-dialog v-model="open" persistent width="500px" scrollable>
    <sv-card :loading="loading" :disabled="loading">
      <sv-card-title @click:close="closeDialog"> 
        {{ id ? $t('UnavailabilityPeriods.titles.edit') : $t('UnavailabilityPeriods.titles.create') }}
      </sv-card-title>
      <sv-card-content>
        <validation-observer ref="validationUnavailabilityPeriod">
          <unavailability-periods-form v-model="itemData" :operators="operators" :action="formAction"/>
        </validation-observer>
      </sv-card-content>
      <sv-card-actions>
        <v-spacer />
        <v-btn color="primary" class="mr-3" depressed rounded outlined @click="closeDialog">
          {{ $t('Users.buttons.cancel') }}
        </v-btn>
        <v-btn color="primary" depressed rounded @click="saveItem">
          {{ $t('Users.buttons.accept') }}
        </v-btn>
      </sv-card-actions>
    </sv-card>
  </v-dialog>
</template>

<script>
import NotificationMixin from '@mixins/NotificationMixin';
import RecurseSortMixin from '@mixins/RecurseSortMixin';
import Repository from '@api/UnavailabilityPeriodsRepository';
import UnavailabilityPeriodsForm from './CreateEditForm.vue';
import { DateTime } from 'luxon';

const unPeriodBase = () => ({
  operator_id: null,
  operators: null, 
  dsc: '',
  is_unexpected: true,
  bts: null,
  ets: null,
});

const unPeriodCreateFactory = (emergencyWorkPeriod = 6) => ({
  ...unPeriodBase(),
  bts: DateTime.local().toISO(),
  ets: DateTime.local().plus({ hours: Number(emergencyWorkPeriod) }).toISO(),
});

const unPeriodEditFactory = () => ({
  ...unPeriodBase(),
});

export default {
  components: { UnavailabilityPeriodsForm },
  mixins: [NotificationMixin, RecurseSortMixin],
  model: {
    prop: 'open',
    event: 'change',
  },
  props: {    
    id: {
      type: [Number, String],
      default: null,
    },    
    operators: {
      type: Array,
      required: true,
    },
  },
  watch: {
    // Подставляем оператора 
    operators: {
      immediate: true,
      handler() {
        this.assignOperatorObject();
      },
    },
  },
  data() {
    const emergencyWorkPeriod = this.$store?.state?.settings?.emergency_work_period;
    return {
      open: true,
      loading: false,      
      itemData: this.id ? unPeriodEditFactory() : unPeriodCreateFactory(emergencyWorkPeriod),
    };
  },
  computed: {
    formAction() {
      return this.id ? 'edit' : 'create';
    },    
    dialog: {
      get() {
        return this.open;
      },
      set(value) {
        this.$emit('change', value);
      },
    },
  },
  created() {
    const { id } = this;
    if (id) this.loadData();
  },
  methods: {
    assignOperatorObject() {      
      if (!this.id) return;

      const operatorIdRaw = this.itemData?.operator_id;
      if (operatorIdRaw == null || operatorIdRaw === '') return;
      if (!Array.isArray(this.operators) || this.operators.length === 0) return;
      if (this.itemData.operators) return;

      const operatorId = Number(operatorIdRaw);
      if (Number.isNaN(operatorId)) return;

      const found = this.operators.find((o) => Number(o.id) === operatorId);
      if (found) this.itemData.operators = found;
    },
    async saveItem() {
      try {
        if (!(await this.$refs.validationUnavailabilityPeriod.validate())) return console.warn('Form not valid');

        const { itemData, id } = this;
        this.loading = true;
        const { data } = id
          ? await Repository.updateUnavailabilityPeriod(id, itemData)
          : await Repository.createUnavailabilityPeriod(itemData);
        this.$emit('apply');
        this.sendAlert(data.alert);
        this.resetForm();
        this.closeDialog();
      } finally {
        this.loading = false;
      }
    },
    async loadData() {
      try {
        const { id, itemData } = this;
        this.loading = true;
        const { data } = await Repository.getUnPeriodById(id);
      
       Object.assign(itemData, data);
       this.assignOperatorObject();
       itemData.bts = this.formatDateISO(data.bts);
       itemData.ets = this.formatDateISO(data.ets);    
    
      } finally {
        this.loading = false;
      }
    },    
    formatDateISO(date) {
      if (date === null) return '';
      else return DateTime.fromSQL(date).toISO();
    },
    resetForm() {
      const emergencyWorkPeriod = this.$store?.state?.settings?.emergency_work_period;
      this.itemData = unPeriodCreateFactory(emergencyWorkPeriod);
    },
    closeDialog() {
      this.$router.push({ name: 'UnavailabilityPeriods' });
    },    
  },
};
</script>
