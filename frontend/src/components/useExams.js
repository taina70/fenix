import { ref } from "vue";
import api from "../api/axios";

export function useExams() {
  const exams = ref([]);
  const exam = ref(null);
  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchExams = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get("/exams", { params });
      exams.value = response.data.data || response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao carregar a lista de provas.";
    } finally {
      loading.value = false;
    }
  };

  const fetchStudentHistory = async (studentId) => {
    try {
      const response = await api.get(`/students/${studentId}/attempts`);
      return response.data.data || [];
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        "Erro ao carregar o histórico de provas.";
      throw err;
    }
  };

  const fetchExamById = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/exams/${id}`);
      exam.value = response.data.data || response.data;
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
      const newExam = response.data.data || response.data;

      exams.value.unshift(newExam);
      return newExam;
    } catch (err) {
      error.value = err.response?.data?.message || "Erro ao criar a prova.";
      throw err;
    } finally {
      submitting.value = false;
    }
  };

  const submitExam = async (examId, payload) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await api.post(`/exams/${examId}/submit`, payload);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Erro ao enviar as respostas da prova.";
      throw err;
    } finally {
      submitting.value = false;
    }
  };

   const updateExam = async (id, payload) => {
     submitting.value = true;
     error.value = null;
     try {
       const response = await api.put(`/exams/${id}`, payload);
       return response.data;
     } catch (err) {
       error.value =
         err.response?.data?.message || "Erro ao atualizar a prova.";
       throw err;
     } finally {
       submitting.value = false;
     }
   };

    const deleteExam = async (id) => {
      error.value = null;
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
    fetchStudentHistory,
    fetchExamById,
    createExam,
    submitExam,
    updateExam,
    deleteExam,
  };
}
