import { apiClient } from "../lib/apiClient";

export function getStaffPerformanceChart(params = {}) {
  const query = new URLSearchParams();

  if (params.date) {
    query.set("date", params.date);
  }

  if (params.month) {
    query.set("month", params.month);
  }

  const suffix = query.toString() ? `?${query.toString()}` : "";

  return apiClient(`/dashboard/staff-performance${suffix}`);
}

export function getRevenueOverview(months = 3) {
  return apiClient(`/dashboard/revenue-overview?months=${months}`);
}
