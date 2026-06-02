<template>
  <v-container fluid class="pt-4 pl-3 pr-3">
    <v-row no-gutters class="tabs-wrapper pb-0 ml-1 mr-1">
      <v-tabs v-model="activeTab" height="2em" show-arrows>
        <v-tab v-for="(item, i) in tabs" :key="i">
          {{ item.nm }}
        </v-tab>
      </v-tabs>
    </v-row>

    <v-row v-if="loadedData && cardData.current_works.length > 0" class="pt-3" no-gutters>
      <v-col class="pr-1 pb-0 pl-1" cols="12" sm="12" md="12">
        <sv-title-card-vidget
          shadow
          border
          :title="translations.currentWorksName"
          :label1="translations.currentWorksStart"
          :label2="translations.currentWorksEnd"
          :works="cardData.current_works"
        >
        </sv-title-card-vidget>
      </v-col>
    </v-row>

    <v-row class="pt-3 limit-width" no-gutters>
      <v-col class="pr-0 pt-0" cols="4">
        <v-tabs-items v-model="activeTab">
          <v-tab-item v-for="(item, i) in tabs" :key="i">
            <sv-card-vidget
              v-show="loadedOperatorData"
              class="mb-4 ml-1 mr-3 mt-1"
              shadow
              border
              icon="mdi-alpha-i-box-outline"
              :title="translations.actProcesses"
              :height="timePanelHeight"
              :label1="isAdminTab ? '' : $t('Dashboard.incoming')"
              :value1="getActProcIn"
              :label2="isAdminTab ? '' : $t('Dashboard.outgoing')"
              :value2="getActProcOut"
              status="info"
            ></sv-card-vidget>
          </v-tab-item>
        </v-tabs-items>

        <sv-table-vidget
          v-if="loadedData"
          ref="capacityTable"
          class="ml-1 mr-3 mb-1"
          shadow
          border
          dense
          :table-name="'capacityTable'"
          :period="$t('Dashboard.periods.dataForToday')"
          :headers="headers_capacity"
          :loading="loadedData.value"
          :items="cardData.all_msisdn.data"
          :counters="getCapacityCounters"
          auto-height
        >
        </sv-table-vidget>
      </v-col>

      <v-col class="pr-1 pt-0" cols="4">
        <v-tabs-items v-model="activeTab">
          <v-tab-item v-for="(item, i) in tabs" :key="i">
            <sv-card-vidget
              v-show="loadedData"
              class="mb-4 mr-3 ml-1 mt-1"
              shadow
              border
              icon="mdi-transit-connection-variant"
              :title="$t('Dashboard.numTransfer')"
              :height="timePanelHeight"
              :label1="isAdminTab ? '' : $t('Dashboard.incoming')"
              :value1="getActMsisdnPortsIn"
              :label2="isAdminTab ? '' : $t('Dashboard.outgoing')"
              :value2="getActMsisdnPortsOut"
              status="info"
            >
            </sv-card-vidget>
          </v-tab-item>
        </v-tabs-items>

        <sv-table-vidget
          v-if="loadedData"
          ref="changeMsisdnTable"
          class="ml-1 mr-3 mb-1"
          shadow
          border
          dense
          :table-name="'msisdnPortsTable'"
          :headers="headers_change_msisdns"
          :loading="loadedChangeMsisdnData.value"
          :items="changeMsisdnData.data"
          :total-height="capacityTableHeight"
          :mode-period="selectedMsisdnModePeriod"
          periods
          :mode-periods="[$t('Dashboard.periods.today'), $t('Dashboard.periods.allTime')]"
          :counters="getChangeMsisdnCounters"
          auto-reload
          @refresh="(...args) => loadChangeMsisdns(...args)"
        />
      </v-col>

      <v-col class="pr-1 pt-0" cols="4">
        <sv-card-vidget
          v-show="loadedData"
          ref="timePanel"
          class="mb-4 mt-1"
          shadow
          border
          icon="mdi-clock-time-four-outline"
          :title="$t('Dashboard.currentTime')"
          :value1="currentTime"
          status="info"
        >
          <template slot="title-right">
            <v-tooltip bottom>
              <template #activator="{ on }">
                <v-icon size="20" class="title-icon" v-on="on" v-text="'mdi-alert-circle-outline'" />
              </template>
              {{ getHintEndPeriod }}
            </v-tooltip>
          </template>

          <template #[timeSlotName]>
            <sv-badge-adv
              outlined
              small
              :badge-class="getBgr(getClassColor())"
              :text-color="getClassColor()"
              :text="getWorkTime()"
            >
            </sv-badge-adv>
          </template>
        </sv-card-vidget>

        <sv-table-vidget
          v-if="loadedData"
          ref="avgPortsTable"
          shadow
          border
          dense
          :table-name="'avgPortsTimeTable'"
          :headers="headers_avg_ports"
          :loading="loadedAvgPortsData.value"
          :items="avgPortsData.data"
          :total-height="capacityTableHeight"
          periods
          :mode-periods="[$t('Dashboard.periods.today'), $t('Dashboard.periods.last7Days')]"
          :counters="getAvgPortableCounters"
          auto-reload
          @refresh="(...args) => loadAvgPorts(...args)"
        >
        </sv-table-vidget>
      </v-col>
    </v-row>

    <v-tabs-items v-if="loadedOperatorData" v-model="activeTab">
      <v-tab-item v-for="(item, i) in tabs" :key="i">
        <v-row class="pt-3 pr-0 pl-1" no-gutters>
          <v-col cols="12" class="pr-1">
            <sv-chart-card
              ref="processChart"
              :key="$i18n.locale"
              type="bar"
              height="250px"
              shadow
              border
              online
              show-link
              :open-link="goProcess"
              :title="$t('Dashboard.processTitle')"
              :chart-options="chartOptionsProcess"
              @fetch="(...args) => loadChartProcess(i, ...args)"
            >
            </sv-chart-card>
          </v-col>
        </v-row>

        <v-row class="pt-4 pr-0 pl-1" no-gutters>
          <v-col cols="12" class="pr-1">
            <sv-table-vidget
              ref="processErrorsTable"
              shadow
              border
              dense
              show-error-panel
              :table-name="'processErrorsTable'"
              :title="$t('Dashboard.processErrors.title')"
              :headers="headers_process_errors"
              :loading="loadedOperatorData.value"
              :items="cardOperatorData.process_errors.data"
              :counters="getProcessErrorsCounters"
              :total-height="300"
              :auto-height="cardOperatorData.process_errors.data.length < 8"
            >
              <template #title-bottom>
                <sv-table-indicator
                  v-if="cardOperatorData.process_errors.data.length > 0"
                  :labels="labels_process_errors"
                  :percent="getProcessErrorsPercent"
                ></sv-table-indicator>
              </template>
            </sv-table-vidget>
          </v-col>
        </v-row>

        <v-row class="pt-4 pr-0 pb-1 pl-1" no-gutters>
          <v-col cols="12" class="pr-1">
            <sv-table-vidget
              ref="apiErrorsTable"
              shadow
              border
              dense
              show-error-panel
              :table-name="'apiErrorsTable'"
              :title="$t('Dashboard.apiErrors.title')"
              :headers="headers_api_errors"
              :loading="loadedOperatorData.value"
              :items="cardOperatorData.api_errors.data"
              :counters="getApiErrorsCounters"
              :total-height="300"
              :auto-height="cardOperatorData.api_errors.data.length < 8"
            >
            </sv-table-vidget>
          </v-col>
        </v-row>
      </v-tab-item>
    </v-tabs-items>

    <template v-if="loadedData">
      <v-row class="pt-3 pr-0 mb-4 pl-1" no-gutters>
        <v-col cols="6" class="pr-4">
          <sv-chart-card
            ref="msisdnPortsChart"
            :key="$i18n.locale"
            type="bar"
            height="250px"
            shadow
            border
            online
            periods
            :title="$t('Dashboard.msisdnPortsTitle')"
            :chart-options="chartOptionsMsisdnPorts"
            @fetch="(...args) => loadChartMsisdnPorts(...args)"
          >
          </sv-chart-card>
        </v-col>
        <v-col cols="6" class="pl-0 pr-1">
          <sv-chart-card
            ref="successProcessChart"
            :key="$i18n.locale"
            type="bar"
            height="250px"
            shadow
            border
            online
            periods
            :title="getSucProcTitle"
            :chart-options="chartOptionsSuccessProcess"
            @fetch="(...args) => loadChartSuccessProcess(...args)"
          >
          </sv-chart-card>
        </v-col>
      </v-row>
    </template>
  </v-container>
</template>

<script>
import SvChartCard from '@components/from_ui_kit/SvCharts/SvChartCard.vue';
import SvBadgeAdv from '@components/common/SvBadgeAdv.vue';
import SvCardVidget from './SvCardVidget.vue';
import SvTitleCardVidget from './SvTitleCardVidget.vue';
import SvTableVidget from './SvTableVidget.vue';
import SvTableIndicator from './SvTableIndicator.vue';
import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
  nextTick,
  onBeforeUnmount,
} from '@vue/composition-api';
import DashboardRepository from '@/api/DashboardRepository';
import { useChartOptions } from '@/hooks/useChartOptions';
import i18n from '@/plugins/i18n';
import { sumColumn } from '@/utils/HelperFunctions';
import store from '@/store';
import moment from 'moment-timezone';
import router from '@/router';

const chartBarParams = (series, parameters, transPart, isTotal) => {
  return {
    color: series.colors,
    xAxis: {
      data: [...parameters],
    },
    series: series.names.map((item, seriesIndex) => ({
      name: transPart ? i18n.t(`${transPart}.${item}`) : item,
      barWidth: 30,
      barGap: '0%',
      itemStyle: {
        color: function () {
          if (series.colors && Array.isArray(series.colors) && series.colors[seriesIndex]) {
            return series.colors[seriesIndex];
          }
          // Цвет по умолчанию, если цвета не заданы или индекс вне диапазона
          return '#5470C6';
        },
      },
      stack: isTotal ? 'total' : null,
    })),
    yAxis: {
      splitNumber: 3,
      max: function (value) {
        return (
          (value.max > 1 ? Math.round(value.max) : Math.round(value.max * 10) / 10) +
          (value.max > 1 ? Math.ceil(value.max * 0.4) : Math.round(value.max * 0.4 * 10) / 10)
        );
      },
      name: i18n.t('Dashboard.quantity'),
    },
  };
};

async function _loadChartData(
  params,
  loading,
  getChartOptions,
  chartOptions,
  chartRef,
  type = 'bar',
  operator_id,
  tabIndex,
  chartType,
) {
  if (!chartType) return;

  loading.value = true;
  try {
    let { data } = {};
    let transPart = 'Dashboard.';
    let isTotal = false;

    if (chartType === 'process') {
      ({ data } = await DashboardRepository.getProcess(operator_id));
      transPart += 'process';
    }

    if (chartType === 'msisdn_ports') {
      ({ data } = await DashboardRepository.getMsisdnPorts(params.period));
      transPart += 'msisdnPorts';
    }

    if (chartType === 'success_processes') {
      ({ data } = await DashboardRepository.getSuccessProcesses(params.period, i18n.locale));
      transPart = null;
      isTotal = true;
    }

    if (!data || Object.keys(data).length === 0) return;

    const gettedObj = getChartOptions(
      type,
      chartOptions.value,
      params,
      data,
      chartBarParams(data.series, data.parameters, transPart, isTotal),
    );

    chartOptions.value = gettedObj;

    if (
      chartRef.value &&
      Array.isArray(chartRef.value) &&
      chartRef.value[tabIndex] &&
      typeof chartRef.value[tabIndex].refresh === 'function'
    ) {
      chartRef.value[tabIndex].refresh(params.mode === 'refresh');
    } else {
      if (typeof chartRef.value.refresh === 'function') {
        chartRef.value.refresh(params.mode === 'refresh');
      }
    }
  } finally {
    loading.value = false;
  }
}

export default {
  components: { SvCardVidget, SvTitleCardVidget, SvBadgeAdv, SvTableVidget, SvChartCard, SvTableIndicator },
  setup() {
    const capacityTable = ref(null);
    const capacityTableHeight = ref(0);

    const timePanel = ref(null);
    const timePanelHeight = ref(0);

    const tabs = ref([]);
    const cardData = ref([]);
    const cardOperatorData = ref([]);
    const changeMsisdnData = ref({});
    const avgPortsData = ref({});
    const activeTab = ref(null);
    const currentTime = ref('');
    const isWorkTime = ref(false);

    let operator_id = ref(null);
    let isAdminTab = ref(true);

    const selectedMsisdnModePeriod = ref(0);

    const showServerTime = ref(false);
    const frozenTime = ref(false);

    const isImeiReqHandlers = ref(0);
    const processChart = ref();
    const msisdnPortsChart = ref();
    const successProcessChart = ref();
    const msisdnPortsPeriod = ref();
    const successProcessPeriod = ref();

    const processLoading = ref(false);
    const msisdnPortsLoading = ref(false);
    const successProcessLoading = ref(false);

    const chartOptionsProcess = ref(null);
    const chartOptionsMsisdnPorts = ref(null);
    const chartOptionsSuccessProcess = ref(null);

    const request = ref([]);
    const response = ref([]);
    const stdresponse = ref([]);

    const maxDateRes = ref();
    const maxValueRes = ref();

    const loadedData = ref(false);
    const firstLoadDone = ref(false);
    const loadedChangeMsisdnData = ref(false);
    const loadedAvgPortsData = ref(false);
    const loadedOperatorData = ref(false);

    const getSucProcTitle = computed(() => {
      return i18n.t('Dashboard.successProcesscTitle');
    });

    const headers_capacity = computed(() => [
      { text: i18n.t('Dashboard.operator'), value: 'operator_name', sortable: true },
      { text: i18n.t('Dashboard.capacityHeaders.capacity'), value: 'quantity', sortable: true },
    ]);

    const headers_change_msisdns = computed(() => [
      { text: i18n.t('Dashboard.operator'), value: 'operator_name', sortable: true },
      { text: i18n.t('Dashboard.portsHeaders.cnt_in'), value: 'cnt_in', sortable: true },
      { text: i18n.t('Dashboard.portsHeaders.cnt_out'), value: 'cnt_out', sortable: true },
    ]);

    const headers_avg_ports = computed(() => [
      { text: i18n.t('Dashboard.operator'), value: 'operator_name', sortable: true },
      { text: i18n.t('Dashboard.portsHeaders.cnt_in'), value: 'cnt_in', sortable: true },
      { text: i18n.t('Dashboard.portsHeaders.cnt_out'), value: 'cnt_out', sortable: true },
    ]);

    const headers_api_errors = computed(() => {
      if (isAdminTab.value) {
        return [
          { text: i18n.t('Dashboard.operator'), value: 'operator_name', sortable: true },
          { text: i18n.t('Dashboard.errors.cnt_errors_short'), value: 'error_cnt', sortable: true },
          { text: i18n.t('Dashboard.errors.last_error'), value: 'last_error_dsc', sortable: true },
          { text: i18n.t('Dashboard.errors.date_last_error'), value: 'last_error_dt', sortable: true },
          { text: i18n.t('Dashboard.errors.date_last_succ_mes'), value: 'last_success_dt', sortable: true },
        ];
      } else {
        return [
          { text: i18n.t('Dashboard.apiErrors.error'), value: 'last_error_dsc', sortable: true },
          { text: i18n.t('Dashboard.apiErrors.date_error'), value: 'last_error_dt', sortable: true },
        ];
      }
    });

    const headers_process_errors = computed(() => {
      return [
        { text: i18n.t('Dashboard.processErrors.recipient_nm'), value: 'recipient_nm', sortable: true },
        { text: i18n.t('Dashboard.errors.cnt_errors_short'), value: 'error_cnt', sortable: true },
        { text: i18n.t('Dashboard.processErrors.perc'), value: 'perc', sortable: true },
        { text: i18n.t('Dashboard.errors.last_error'), value: 'last_error_nm', sortable: true },
        { text: i18n.t('Dashboard.errors.date_last_error'), value: 'last_error_dt', sortable: true },
      ];
    });

    const labels_process_errors = computed(() => {
      return [
        i18n.t('Dashboard.errorValues.low'),
        i18n.t('Dashboard.errorValues.average'),
        i18n.t('Dashboard.errorValues.high'),
      ];
    });

    const getApiErrorsCounters = computed(() => {
      const total = [
        {
          label: i18n.t('Dashboard.errors.cnt_errors'),
          value: formatValue(cardOperatorData.value?.api_errors?.error_total),
        },
      ];

      if (isAdminTab.value) {
        return [
          ...total,
          {
            label: i18n.t('Dashboard.errors.date_last_error'),
            value: cardOperatorData.value?.api_errors?.api_last_error_dt_last_row,
          },
          {
            label: i18n.t('Dashboard.errors.last_error'),
            value: cardOperatorData.value?.api_errors?.api_last_error_dsc_last_row,
          },
        ];
      } else {
        return [
          ...total,
          {
            label: i18n.t('Dashboard.errors.last_error'),
            value: cardOperatorData.value?.api_errors?.api_last_error_dsc_last_row,
          },
          {
            label: i18n.t('Dashboard.errors.date_last_succ_mes'),
            value: cardOperatorData.value?.api_errors?.api_last_success_dt_last_row,
          },
        ];
      }
    });

    const getProcessErrorsPercent = computed(() => {
      return cardOperatorData.value?.process_errors?.error_percent ?? 0;
    });

    const getActProcIn = computed(() => {
      return cardOperatorData.value?.active_processes?.cnt_in ?? 0;
    });

    const getActProcOut = computed(() => {
      return isAdminTab.value ? '' : cardOperatorData.value?.active_processes?.cnt_out ?? 0;
    });

    const getActMsisdnPortsIn = computed(() => cardOperatorData.value?.active_msisdns?.cnt_in ?? 0);

    const getActMsisdnPortsOut = computed(() => {
      return isAdminTab.value ? '' : cardOperatorData.value?.active_msisdns?.cnt_out ?? 0;
    });

    const getHintEndPeriod = computed(() => {
      const endPeriod = getEndPeriod.value;
      return (
        i18n.t('Dashboard.currentTimeHint') +
        (!frozenTime.value && endPeriod && endPeriod.isValid() ? endPeriod.format('HH:mm:ss') : '...')
      );
    });

    const getProcessErrorsCounters = computed(() => {
      return [
        {
          label: i18n.t('Dashboard.errors.cnt_errors'),
          value: formatValue(cardOperatorData.value?.process_errors?.error_total),
        },
        {
          label: i18n.t('Dashboard.errors.date_last_error'),
          value: cardOperatorData.value?.process_errors?.process_last_error_dt_last_row,
        },
        {
          label: i18n.t('Dashboard.errors.last_error'),
          value: cardOperatorData.value?.process_errors?.process_last_error_nm_last_row,
        },
      ];
    });

    const getCapacityCounters = computed(() => {
      return [
        {
          label: i18n.t('Dashboard.operatorsCapacity'),
          value: cardData.value?.all_msisdn?.total ?? '—',
        },
      ];
    });

    const getChangeMsisdnCounters = computed(() => {
      return [{ label: i18n.t('Dashboard.changeMsisdn'), value: changeMsisdnData.value?.total ?? '—' }];
    });

    const getAvgPortableCounters = computed(() => {
      return [{ label: i18n.t('Dashboard.avgPortableTime'), value: avgPortsData.value?.total ?? '—' }];
    });

    const operatorId = computed(() => activeTabId(activeTab.value));

    watch(activeTab, (newVal) => {
      operator_id.value = activeTabId(newVal);

      isAdminTab.value = operator_id.value == null;

      nextTick(() => {
        startUpdateOperatorInterval(operator_id.value, i18n.locale);
      });

      nextTick(() => {
        updateTimePanelHeight();
      });
    });

    watch(loadedData, (loaded) => {
      // получаем высоту capacityTable, чтобы выставить такую же на соседних в ряду таблицах
      // иначе она может плававть из-за разного количества строк в таблицах
      if (loaded) {
        updateTableHeight();
        updateTimePanelHeight();
      }
    });

    watch(
      () => i18n.locale,
      (newLocale) => {
        startUpdateOperatorInterval(operator_id.value, newLocale);
      },
    );

    const activeTabId = (activeTab) => {
      return tabs.value[activeTab] ? tabs.value[activeTab].id : null;
    };

    const formatValue = (value) => {
      if (value === 0 || value === '' || value === null || value === undefined) {
        return '—';
      } else {
        return new Intl.NumberFormat('ru-RU').format(value);
      }
    };

    const sumData = (data, column) => {
      return sumColumn(data, column).toLocaleString('ru-RU');
    };

    const updateTableHeight = () => {
      nextTick(() => {
        const el = capacityTable.value?.$el;
        if (el && el.clientHeight) {
          capacityTableHeight.value = el.clientHeight - 138;
        }
      });
    };

    const updateTimePanelHeight = () => {
      nextTick(() => {
        const el = timePanel.value?.$el;
        if (el && el.clientHeight) {
          timePanelHeight.value = el.clientHeight + 2;
        }
      });
    };

    // Функция задает значение переменной showServerTime, которая необходима для определения
    // откуда брать время с сервера или клиента
    const checkTime = () => {
      showServerTime.value = false;

      const diffTime = 2; // интервал (1 мин на таймер для запроса в БД, 1 мин на редис)
      const differenceMin = Math.abs(userCurrentTime().diff(serverCurrentTime.value, 'minutes'));

      showServerTime.value = differenceMin < diffTime ? false : true;
    };

    // Функция записывает текущее время в currentTime (пользовательское или серверное)
    // Если у клиента время выставлено не корректно (значительно отличается от серверного), то берет время с сервера, иначе с клиента
    // Также функция производит заранее смену рабочего/нерабочего времени isWorkTime в зависимости от end_period
    // Смена времени производится с заморозкой на 2 минуты (так как за это время все еще могут придти устаревшие данные с бд)
    const updateTime = () => {
      const now = userCurrentTime();

      currentTime.value = showServerTime.value
        ? serverCurrentTime.value?.format('HH:mm')
        : now?.format('HH:mm:ss');

      // Если берется серверное время, тогда обновляем данные на плашке "Текущее время", как только они придут с бэка.
      // Если берется время пользователя, то обновляем данные онлайн и на 2 минуты с помощью переменной frozenTime запрещаем изменение,
      // иначе могут придти старые данные с бэка (обновление 1 раз в мин + на мин кладется в редис)
      if (showServerTime.value) return;

      const curTime = now;

      //console.log('curTime', curTime.format('HH:mm:ss'));
      //console.log('getEndPeriod.value', getEndPeriod.value?.format('HH:mm:ss'));

      if (!frozenTime.value && getEndPeriod.value && curTime) {
        const difference = Math.abs(getEndPeriod.value.diff(curTime, 'seconds'));
        //console.log('differenceMin()', difference);
        if (difference <= 0) {
          const serverIsWorkTime = cardData.value?.work_time?.is_work_time;

          isWorkTime.value = !serverIsWorkTime;
          frozenTime.value = true;

          setTimeout(() => {
            frozenTime.value = false;
          }, 2 * 60 * 1000);
        }
      }
    };

    const timeSlotName = computed(() => 'label2-' + (isAdminTab.value ? 'right' : 'bottom'));

    const cardHeight = computed(() => (isAdminTab.value ? 100 : 120));

    const loadTabs = async () => {
      try {
        const { data } = await DashboardRepository.getTabs();

        tabs.value = data;

        // Добавляем вкладку "Общая сводка"
        tabs.value.unshift({ id: null, nm: i18n.t('Dashboard.tabTotal') });
      } catch (error) {
        this.sendError(error);
      }
    };

    loadTabs();

    const updateData = () => {
      loadData();
    };

    const goProcess = () => {
      const setFilterTimeout = true;
      const routeData = router.resolve({
        name: 'Process',
        query: { setFilterTimeout },
      });

      window.open(routeData.href, '_blank');
    };

    const updateOperatorData = (operator, locale) => {
      loadOperatorData(operator, locale);
    };

    let updateInterval = null;
    let updateOperatorInterval = null;
    let updateTimeInterval = null;

    const startUpdateOperatorInterval = (operator, locale) => {
      if (updateOperatorInterval) clearInterval(updateOperatorInterval);

      updateOperatorData(operator, locale); // сразу вызвать при смене operatorId

      updateOperatorInterval = setInterval(() => {
        updateOperatorData(operator, locale);
      }, 60000);
    };

    onBeforeUnmount(() => {
      window.removeEventListener('resize', updateTableHeight);
      window.removeEventListener('resize', updateTimePanelHeight);
    });

    onMounted(() => {
      updateData();
      startUpdateOperatorInterval(operatorId.value, i18n.locale);
      updateTime();
      loadAvgPorts(0);
      loadChangeMsisdns(0);

      // обновлять каждую минуту
      updateInterval = setInterval(updateData, 60000);

      // обновлять каждую секунду
      updateTimeInterval = setInterval(updateTime, 1000);

      window.addEventListener('resize', updateTableHeight);
      window.addEventListener('resize', updateTimePanelHeight);
    });

    onUnmounted(() => {
      // очистить интервал при размонтировании компонента
      clearInterval(updateInterval);
      clearInterval(updateTimeInterval);

      if (updateOperatorInterval) {
        clearInterval(updateOperatorInterval);
        updateOperatorInterval = null;
      }
    });

    const getEndPeriod = computed(() => {
      return cardData.value?.work_time?.end_period
        ? moment(cardData.value?.work_time?.end_period).tz(userTimeZone.value)
        : null;
    });

    const serverCurrentTime = computed(() => {
      return cardData.value?.work_time?.current_time
        ? moment(cardData.value?.work_time?.current_time).tz(userTimeZone.value)
        : null;
    });

    const userTimeZone = computed(() => store.getters.getTimezone);

    const userCurrentTime = () => {
      return moment().tz(userTimeZone.value);
    };

    // Перенесенные номера
    const loadChangeMsisdns = async (period) => {
      try {
        const { data } = await DashboardRepository.getChangeMsisdns(period);
        changeMsisdnData.value = data;
        selectedMsisdnModePeriod.value = period;
      } catch (error) {
        this.sendError(error);
      }

      loadedChangeMsisdnData.value = true;
    };

    // Среднее время портации
    const loadAvgPorts = async (period) => {
      try {
        const { data } = await DashboardRepository.getAvgPorts(period);
        avgPortsData.value = data;
      } catch (error) {
        this.sendError(error);
      }

      loadedAvgPortsData.value = true;
    };

    const loadData = async () => {
      try {
        const { data } = await DashboardRepository.getData();
        cardData.value = data;
      } catch (error) {
        this.sendError(error);
      }

      const isWorkTimeServer = cardData.value?.work_time?.is_work_time || false;

      if (!frozenTime.value) {
        isWorkTime.value = isWorkTimeServer;
      }

      loadedData.value = true;

      if (!firstLoadDone.value) {
        checkTime();
        firstLoadDone.value = true;
      }
    };

    const loadOperatorData = async (operator, locale) => {
      try {
        const { data } = await DashboardRepository.getOperatorData(operator, locale);
        cardOperatorData.value = data;
      } catch (error) {
        this.sendError(error);
      }

      loadedOperatorData.value = true;
    };

    //loadData(activeTab.value);

    const getClassColor = () => {
      return isWorkTime.value ? 'success' : 'error';
    };

    const getColor = () => {
      return isWorkTime.value ? 'green' : 'red';
    };

    const getBgr = (class_color) => {
      let res = 'time-chip ml-4 border: 1px solid ' + class_color + ' ';
      switch (class_color) {
        case 'success':
          res += 'chipSuccessBg';
          break;
        case 'error':
          res += 'chipErrorBg';
          break;
      }; 
      return res;
    };

    const { getChartOptions } = useChartOptions();

    const translations = computed(() => ({
      actProcesses: i18n.t('Dashboard.actProcesses'),
      numTransfer: i18n.t('Dashboard.numTransfer'),
      currentTime: i18n.t('Dashboard.currentTime'),
      currentWorksName: i18n.t('Dashboard.currentWorks.name'),
      currentWorksStart: i18n.t('Dashboard.currentWorks.start'),
      currentWorksEnd: i18n.t('Dashboard.currentWorks.end'),
      incoming: i18n.t('Dashboard.incoming'),
      outgoing: i18n.t('Dashboard.outgoing'),
    }));

    const loadChartProcess = (i, params) => {
      if (i === activeTab.value) {
        _loadChartData(
          params,
          processLoading,
          getChartOptions,
          chartOptionsProcess,
          processChart,
          'bar',
          operator_id.value,
          activeTab.value,
          'process',
        );
      }
    };

    const loadChartMsisdnPorts = (params) => {
      _loadChartData(
        params,
        msisdnPortsLoading,
        getChartOptions,
        chartOptionsMsisdnPorts,
        msisdnPortsChart,
        'bar',
        operator_id.value,
        ref(null),
        'msisdn_ports',
      );
      msisdnPortsPeriod.value = params.period;
    };

    const loadChartSuccessProcess = (params) => {
      _loadChartData(
        params,
        successProcessLoading,
        getChartOptions,
        chartOptionsSuccessProcess,
        successProcessChart,
        'bar',
        operator_id.value,
        ref(null),
        'success_processes',
      );
      successProcessPeriod.value = params.period;
    };

    const getWorkTime = () => {
      return isWorkTime.value ? i18n.t('Dashboard.workingTime') : i18n.t('Dashboard.nonWorkingTime');
    };

    return {
      translations,
      loadChartProcess,
      loadChartMsisdnPorts,
      loadChartSuccessProcess,
      loadChangeMsisdns,
      loadAvgPorts,
      processChart,
      msisdnPortsPeriod,
      successProcessPeriod,
      msisdnPortsChart,
      successProcessChart,
      chartOptionsProcess,
      chartOptionsMsisdnPorts,
      chartOptionsSuccessProcess,
      isImeiReqHandlers,
      request,
      response,
      stdresponse,
      processLoading,
      msisdnPortsLoading,
      successProcessLoading,
      maxDateRes,
      maxValueRes,
      tabs,
      activeTab,
      operatorId,
      loadData,
      getBgr,
      getClassColor,
      getColor,
      cardData,
      changeMsisdnData,
      avgPortsData,
      getWorkTime,
      loadedData,
      firstLoadDone,
      loadedOperatorData,
      loadedChangeMsisdnData,
      loadedAvgPortsData,
      getEndPeriod,
      serverCurrentTime,
      userCurrentTime,
      userTimeZone,
      activeTabId,
      timeSlotName,
      cardHeight,
      currentTime,
      checkTime,
      frozenTime,
      headers_capacity,
      headers_api_errors,
      headers_process_errors,
      labels_process_errors,
      headers_avg_ports,
      capacityTable,
      capacityTableHeight,
      timePanelHeight,
      timePanel,
      sumData,
      headers_change_msisdns,
      operator_id,
      isAdminTab,
      formatValue,
      getApiErrorsCounters,
      getCapacityCounters,
      getChangeMsisdnCounters,
      getAvgPortableCounters,
      getProcessErrorsCounters,
      getProcessErrorsPercent,
      getActProcIn,
      getActProcOut,
      getActMsisdnPortsIn,
      getActMsisdnPortsOut,
      getHintEndPeriod,
      cardOperatorData,
      startUpdateOperatorInterval,
      goProcess,
      getSucProcTitle,
      selectedMsisdnModePeriod,
    };
  },
};
</script>

<style lang="scss" scoped>
.tabs-wrapper {
  border-bottom: 1px solid var(--v-border-main-base); 
}

.theme--dark .tabs-wrapper {
//  border-bottom: none; 
}

.title-icon {
  position: relative;
  top: -2px;
  color: var(--v-text-secondary-base);
}

.v-tab {
  text-transform: none;
}

.limit-width {
  display: flex;
  overflow-x: auto;
  white-space: nowrap;
  min-width: 100%;
}
</style>
