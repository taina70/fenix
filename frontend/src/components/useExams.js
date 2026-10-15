import { ref } from "vue";
import api from "../api/axios";

export function useExams() {
  const exams = ref([]);
  const exam = ref(null);
  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchExams = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get("/exams");
      exams.value = response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao carregar a lista de provas.";
    } finally {
      loading.value = false;
    }
  };

  const fetchExamById = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/exams/${id}`);
      exam.value = response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao carregar os dados da prova.";
    } finally {
      loading.value = false;
    }
  };

  const createExam = async (payload) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await api.post("/exams", payload);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        "Erro ao cadastrar a prova. Verifique as validações.";
      throw err;
    } finally {
      submitting.value = false;
    }
  };

  const deleteExam = async (id) => {
    try {
      await api.delete(`/exams/${id}`);
      exams.value = exams.value.filter((e) => e.id !== id);
    } catch (err) {
      error.value = err.response?.data?.message || "Erro ao excluir a prova.";
      throw err;
    }
  };

  return {
    exams,
    exam,
    loading,
    submitting,
    error,
    fetchExams,
    fetchExamById,
    createExam,
    deleteExam,
  };
}
