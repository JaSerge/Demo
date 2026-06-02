<template>
  <v-card :class="getClasessVidget" :style="{ height: computedHeight }">
    <v-row class="pt-3 pb-3 pr-4" no-gutters>
      <v-col class="pr-4 pt-2" cols="10">
        <v-row class="d-flex pl-4 pr-4 justify-start">
          <span class="labelVidget ml-4">
            {{ title }}&nbsp;
            <slot name="title-right" />
          </span>
        </v-row>
        <v-row class="d-flex pl-4 pr-4 justify-start">
          <span class="valueVidget ml-4 pt-1">{{ value1 }}</span
          >&nbsp;&nbsp;
          <span class="labelVidget mt-1"> {{ label1 }} <slot name="label2-right" /> </span>
        </v-row>
        <v-row class="d-flex pl-4 pr-4 justify-start">
          <span class="slot-label2-bottom-wrapper">
            <slot name="label2-bottom" />
          </span>
          <template v-if="value2.length !== 0">
            <span class="valueVidget ml-4">{{ value2 }}</span
            >&nbsp;&nbsp;
            <span class="labelVidget mt-1">
              {{ label2 }}
            </span>
          </template>
        </v-row>
      </v-col>
      <v-col class="d-flex justify-end" cols="2" no-gutters>
        <div class="statusBigPoint" :class="getClassColorStatus" align="center">
          <v-icon color="var(--v-background-main-base)" class="pt-2">{{ icon }}</v-icon>
        </div>
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
export default {
  props: {
    parameters: Object,
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
    value1: {
      type: [String, Number],
      default: '',
    },
    value2: {
      type: [String, Number],
      default: '',
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
    height: {
      type: Number,
      default: null,
    },
  },
  data() {
    return {
      minWidth: 300,
    };
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

    computedHeight() {
      // Если height задан и числовой и больше 0 — вернуть с px
      if (this.height && Number(this.height) > 0) {
        return this.height + 'px';
      }
      // Иначе auto для автоматического определения высоты
      return 'auto';
    },
  },
};
</script>
<style lang="scss" scoped>
@import '@components/from_ui_kit/scss/branding.scss';

.statusBigPoint {
  width: 40px;
  height: 40px;
  border-radius: 50px;
}

.labelVidget {
  font-size: 14px;
  color: var(--v-text-secondary-base);
  font-weight: normal;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  overflow: hidden;
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
  // box-shadow: 0 4px 16px rgba(48, 49, 51, 0.1)!important;
}

.card-shadow-none {
  box-shadow: none !important;
}

.sv-status-neutral {
  background: $brand-text;
}
.sv-status-info {
  background: $brand-status-info;
}
.sv-status-ok {
  background: $brand-status-success;
}
.sv-status-error {
  background: $brand-status-danger;
}
.sv-status-warning {
  background: $brand-status-warning;
}

.slot-label2-bottom-wrapper {
  margin-top: 2px;
}
</style>
