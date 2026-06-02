<template>
  <div class="fullscreen">
    <v-container fluid class="pa-0 pt-0">
      <v-row class="px-3 mb-0">
        <v-col
          v-for="(text, index) in labels"
          :key="index"
          :cols="Math.floor(12 / labels.length)"
          class="pa-0 ma-0 labelValues"
          :class="{
            'text-left': index === 0,
            'text-center': index === 1,
            'text-right': index === 2,
          }"
        >
          {{ text }}
        </v-col>
      </v-row>

      <v-row class="mt-0">
        <v-col cols="12" class="px-3">
          <div class="indicator">
            <div class="background-line"></div>
            <div class="line" :style="{ width: percent + '%', backgroundColor: currentColor }"></div>
            <div
              class="dot"
              :style="{
                left: percent + '%',
                backgroundColor: currentColor,
                boxShadow: '0 0 5px ' + currentColor,
              }"
            ></div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
export default {
  name: 'RedLineIndicator',
  props: {
    labels: {
      type: Array,
      default: () => [],
    },
    percent: {
      type: Number,
      default: 50,
    },
  },
  computed: {
    currentColor() {
      if (this.percent <= 20) {
        return '#4CAF50'; // зеленый
      } else if (this.percent <= 50) {
        return '#FF9900'; // желтый
      } else {
        return 'red';
      }
    },
  },
};
</script>

<style scoped>
.fullscreen {
  width: 100%;
  box-sizing: border-box;
}

.labelValues {
  font-size: 10px;
  font-weight: normal;
  color: var(--v-text-secondary-base);
  margin-bottom: -8px !important;
}

.indicator {
  position: relative;
  width: 100%;
  height: 10px;
}

.background-line {
  position: absolute;
  top: 50%;
  left: 0;
  width: 100%;
  height: 4px;
  background-color: #cccccc;
  border-radius: 2px;
  transform: translateY(-50%);
  z-index: 0;
}

.line {
  position: absolute;
  top: 50%;
  left: 0;
  height: 4px;
  border-radius: 2px;
  transform: translateY(-50%);
  z-index: 1;
  /* width и backgroundColor теперь через style */
}

.dot {
  position: absolute;
  top: 50%;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
  /* left, backgroundColor и boxShadow через style */
}
</style>
