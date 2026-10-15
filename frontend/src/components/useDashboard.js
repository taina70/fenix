import { ref } from "vue";
import api from "../api/axios";

export function useDashboard() {
  const stats = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchStats = async (examId = null) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get("/dashboard", {
        params: { exam_id: examId },
      });
      stats.value = response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao carregar dados do dashboard.";
    } finally {
      loading.value = false;
    }
  };

  return { stats, loading, error, fetchStats };
}
