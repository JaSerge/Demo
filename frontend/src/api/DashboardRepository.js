import Repository from '@/plugins/axios';

const resource = 'home-workplace';

export default {
  getTabs() {
    return Repository.get(`${resource}/dashboard-tabs`);
  },
  getData() {
    return Repository.get(`${resource}/dashboard-data`);
  },
  getOperatorData(operator_id, locale) {
    return Repository.get(`${resource}/dashboard-operator_data/${operator_id}?locale=${locale}`);
  },
  getProcess(operator_id) {
    return Repository.get(`${resource}/dashboard-process/${operator_id}`);
  },
  getMsisdnPorts(period) {
    return Repository.get(`${resource}/dashboard-msisdn_ports/${period}`);
  },
  getAvgPorts(period) {
    return Repository.get(`${resource}/dashboard-avg_ports/${period}`);
  },
  getChangeMsisdns(period) {
    return Repository.get(`${resource}/dashboard-change_msisdns/${period}`);
  },
  getSuccessProcesses(period, locale) {
    return Repository.get(`${resource}/dashboard-success_processes/${period}?locale=${locale}`);
  },
};
