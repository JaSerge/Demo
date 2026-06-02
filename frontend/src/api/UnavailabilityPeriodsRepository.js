import Repository from '@/plugins/axios';

const resourses = '/unavailability-periods';

export default {
  getUnavailabilityPeriods(params) {
    return Repository.get(`${resourses}/`, { params });
  },
  getAvailableOperators() {
    return Repository.get(`${resourses}/available-operators`);
  },
  createUnavailabilityPeriod(data) {
    return Repository.post(`${resourses}/`, data);
  },
  updateUnavailabilityPeriod(id, data) {
    return Repository.put(`${resourses}/${id}`, data);
  },
  closeUnavailabilityPeriod(id) {
    return Repository.post(`${resourses}/${id}/close`);
  },  
  getUnPeriodById(id) {
    return Repository.get(`${resourses}/${id}`);
  },
};
