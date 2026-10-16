import { ref } from "vue";
import api from "../api/axios";

export function useDashboard() {
  const stats = ref({
    average_score: 0,
    top_score: 0,
    lowest_score: 0,
    total_attempts: 0,
    ranking: [],
  });

  const loading = ref(false);
  const error = ref(null);

  const fetchStats = async (examId = null) => {
    loading.value = true;
    error.value = null;

    try {
      // Monta os parâmetros apenas se examId for passado explicitamente
      const config = examId ? { params: { exam_id: examId } } : {};
      const response = await api.get("/dashboard", config);

      const data = response.data.data || response.data;

      if (data && typeof data === "object") {
        stats.value = {
          average_score: data.average_score ?? 0,
          top_score: data.top_score ?? 0,
          lowest_score: data.lowest_score ?? 0,
          total_attempts: data.total_attempts ?? 0,
          ranking: Array.isArray(data.ranking)
            ? data.ranking
            : data.ranking?.data || [],
        };
      }
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao carregar dados do dashboard.";
    } finally {
      loading.value = false;
    }
  };

  return { stats, loading, error, fetchStats };
}
