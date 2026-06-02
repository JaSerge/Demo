<template>
  <div>
    <v-row dense>
      <v-col>
        <validation-provider
          v-slot="{ errors }"
          rules="required"
          mode="eager"
          :name="$t('UnavailabilityPeriods.fields.operator')"
        >
          <v-autocomplete
            v-if="!isEditMode"
            v-model="itemData.operators"
            clearable
            outlined
            return-object
            item-text="nm"
            :error-messages="errors"
            :label="$t('UnavailabilityPeriods.fields.operator')"
            :items="operators"
            :disabled="isWorkBegun"
          />
          <v-text-field
            v-else            
            :value="operatorNm"
            readonly
            outlined
            :error-messages="errors"
            :label="$t('UnavailabilityPeriods.fields.operator')"
            disabled
          />
        </validation-provider>
      </v-col>
      </v-row>
      <v-row dense class="mt-n5">
      <v-col>
        <v-checkbox 
          v-model="itemData.is_unexpected" 
          :disabled="isEditMode"
          :label="$t('UnavailabilityPeriods.emergency_situation')" />
      </v-col>
    </v-row>
    <v-row dense>
      <v-col>
        <validation-provider 
          v-slot="{ errors }" 
          :rules="btsRules"
          vid="bts"
          mode="lazy"
          immediate
          :name="$t('UnavailabilityPeriods.fields.bts')">
          <datetime-field
            v-model="itemData.bts"
            datetime
            outlined
            :error-messages="errors"
            :disabled="isWorkBegun || itemData.is_unexpected"
            :label="$t('UnavailabilityPeriods.fields.bts')"            
          />
        </validation-provider>
      </v-col>
    </v-row>  
    <v-row dense>
      <v-col>
        <validation-provider 
          v-slot="{ errors }" 
          :rules="etsRules"
          vid="ets"
          mode="lazy"
          immediate
          :name="$t('UnavailabilityPeriods.fields.ets')">        
          <datetime-field
            v-model="itemData.ets"
            datetime
            outlined          
            :error-messages="errors"
            :label="$t('UnavailabilityPeriods.fields.ets')"            
          />
        </validation-provider>  
      </v-col>
    </v-row>    
    <v-row dense>
      <v-col>
        <validation-provider
          v-slot="{ errors }"
          rules="required|max:500"
          mode="eager"
          :name="$t('UnavailabilityPeriods.fields.description')"
        >
          <v-textarea
            v-model="itemData.dsc"
            counter
            outlined
            :label="$t('UnavailabilityPeriods.fields.description')"
            :error-messages="errors"
          />
        </validation-provider>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { computed, watch, nextTick, inject, ref, watchEffect } from '@vue/composition-api';
import { SvDatetimeField } from '@somevendor/ui-components';
import i18n from '@/plugins/i18n';
import { DateTime } from 'luxon';

export default {
  components: {
    DatetimeField: SvDatetimeField,
  },
  props: {
    action: {
      type: String,
      default: 'create',
    },
    value: {
      type: Object,
      required: true,
      default: () => ({})
    },
    operators: {
      type: Array,      
    },
  },
  setup(props, { emit }) {
    const veeObserver = inject('$_veeObserver', null);

    const isEditMode = ref(false);
    const isWorkBegun = ref(false);
    const isInitialized = ref(false);

    // Edit режим
    watchEffect(() => {
      isEditMode.value = props.action === 'edit' ?  true : false;
      
      if (!isInitialized.value && props.value.bts && isEditMode.value) {        
        isWorkBegun.value = DateTime.fromISO(props.value.bts) <= DateTime.now();
        isInitialized.value = true; 
      }
    });

    // Create режим
    watchEffect(() => {
      if (!isEditMode.value && !isInitialized.value) {     
        isWorkBegun.value = false;
        isInitialized.value = true;
      }
    });

    const itemData = computed({
      get: () => props.value,
      set: (val) => {
        emit('input', val);
      },
    });



    const btsRules = computed(() => {
      if (isWorkBegun.value || itemData.value.is_unexpected) return 'required';
      return 'required|date_after_now';
    });

    const etsRules = computed(() => {
      if (!itemData.value.bts) return 'required';
      return {
        required: true,
        date_after: {
          targetValue: itemData.value.bts,
          targetLabel: i18n.t('UnavailabilityPeriods.fields.bts'),
        },
      };
    });
    
    watch(() => itemData.value.is_unexpected, (newVal) => {
      if (newVal && !isWorkBegun.value || !isEditMode.value) {
        const now = DateTime.now().toISO();
        emit('input', { 
          ...props.value, 
          bts: now 
        });      
      }
    });

    watch([() => itemData.value.bts, () => itemData.value.ets], () => {
      nextTick(() => {
        const btsProvider = veeObserver?.refs?.bts;
        const etsProvider = veeObserver?.refs?.ets;
        if (btsProvider && typeof btsProvider.validate === 'function') {
          btsProvider.validate();
        }
        if (etsProvider && typeof etsProvider.validate === 'function') {
          etsProvider.validate();
        }
      });
    });  

    // если не найден оператор в списке мобильных операторов (например, сменили тип с мобильного), 
    // то используем поле itemData.nm
    const operatorNm = ref();

    watch(
      itemData,
      (newVal) => {
        if (!newVal) return;

        operatorNm.value = newVal?.nm;
      },
      { deep: true }
    );
    
    return {
      itemData,
      isWorkBegun,
      btsRules,
      etsRules,
      isEditMode,
      operatorNm    
    };
  },
};
</script>
