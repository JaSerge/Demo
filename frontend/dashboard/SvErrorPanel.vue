<template>
  <v-row align="center" class="green-border-box" no-gutters>
    <v-col cols="auto">
      <div class="circle-with-check">
        <v-icon color="#4caf50" size="20">mdi-check-circle-outline</v-icon>
      </div>
    </v-col>
    <v-col cols="auto">
      <span class="green-text">{{ text }}</span>
    </v-col>
  </v-row>
</template>

<script>
export default {
  props: {
    text: {
      type: String,
      default: '',
    },
  },
};
</script>

<style scoped>
.green-border-box {
  border: 1px solid #4caf50; 
  background-color: var(--v-lime-base);
  padding: 8px 16px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
}
.circle-with-check {
  display: flex;
  justify-content: center;
  align-items: center;
}

.green-text {
  margin-left: 8px;
  font-weight: 400;
  font-size: 14px;
  color: var(--v-text-main-base);
}
</style>
