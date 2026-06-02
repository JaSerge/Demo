<template>
  <v-card :class="getClasessVidget">
    <v-row class="d-flex justify-start pt-3 pr-4 pl-4" no-gutters>
      <div style="display: flex; align-items: center">
        <span style="font-size: 8px">&#128308;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="titleVidget">{{ title }}</span>
      </div>
    </v-row>
    <v-row class="d-flex justify-start pt-2 pb-4 pr-4 pl-4" no-gutters>
      <v-col v-for="(work, index) in works" :key="index" class="pr-4 pt-0 pl-0" cols="auto">
        <v-card class="childVidget">
          <v-row class="d-flex justify-start pl-4 pb-1 pt-2" no-gutters>
            <span class="operatorVidget">{{ work.operator }}</span>
          </v-row>
          <v-row class="d-flex justify-start pl-4 pr-4" no-gutters>
            <span class="labelVidget">{{ label1 }}:&nbsp;</span>
            <span class="valueVidget">{{ work.sd }}</span>
          </v-row>
          <v-row class="d-flex justify-start pl-4 pr-4 pb-2" no-gutters>
            <span class="labelVidget">{{ label2 }}:&nbsp;</span>
            <span class="valueVidget">{{ work.ed }}</span>
          </v-row>
        </v-card>
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
export default {
  props: {
    icon: {
      type: String,
      default: '',
    },
    title: {
      type: String,
      default: '',
    },
    label1: {
      type: String,
      default: '',
    },
    label2: {
      type: String,
      default: '',
    },
    works: {
      type: Array,
      required: true,
    },
    status: {
      type: String,
      default: 'neutral',
    },
    border: {
      type: Boolean,
      default: false,
    },
    shadow: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    getClassColorStatus() {
      const st = this.status;
      return `sv-status-${st}`;
    },

    getClasessVidget() {
      return [this.border ? 'borderVidget' : '', this.shadow ? 'card-shadow' : 'card-shadow-none']
        .filter(Boolean)
        .join(' ');
    },
  },
};
</script>
<style lang="scss" scoped>
@import '@/components/from_ui_kit/scss/branding.scss';

.statusBigPoint {
  width: 40px;
  height: 40px;
  border-radius: 50px;
}

.labelVidget {
  color: var(--v-text-secondary-base);
  font-size: 14px;
}

.valueVidget {
  color: var(--v-text-main-base);
  font-weight: 300;
  font-size: 14px;
}

.titleVidget {
  color: var(--v-text-secondary-base);
  font-size: 14px;
}

.operatorVidget {
  color: var(--v-text-main-base);
  font-weight: 500;
  font-size: 14px;
}

.borderVidget { 
  border: 1px solid var(--v-border-main-base);
}

.card-shadow {
  // box-shadow: 0px 2px 16px 0px #3031331a, 0px 1px 2px 0px #30313314 !important;
}

::v-deep .childVidget {
  box-shadow: none !important;
  background: var(--v-rose-base) !important;
  max-width: 280px;
  word-break: break-word;
  border: 1px solid var(--v-border-main-base);
}

.card-shadow-none {
  box-shadow: none !important;
}
</style>
