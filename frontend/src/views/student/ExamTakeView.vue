<template>
  <div v-if="loading">Carregando questões...</div>
  <div v-else-if="exam">
    <h2>{{ exam.title }}</h2>
    <p>{{ exam.description }}</p>

    <form @submit.prevent="submitExam">
      <div class="user-id-box">
        <label>ID do Aluno (Simulação): </label>
        <input type="number" v-model.number="studentId" required min="1" />
      </div>

      <div v-for="(q, index) in exam.questions" :key="q.id" class="question-card">
        <h4>Questão {{ index + 1 }}: {{ q.statement }} ({{ q.points }} pts)</h4>
        
        <div v-for="opt in q.options" :key="opt.id" class="option-item">
          <label>
            <input 
              type="radio" 
              :name="`question_${q.id}`" 
              :value="opt.id" 
              v-model="answers[q.id]" 
              required 
            />
            {{ opt.text }}
          </label>
        </div>
      </div>

      <button type="submit" :disabled="submitting" class="btn-submit">
        {{ submitting ? 'Enviando...' : 'Finalizar e Enviar Prova' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/axios';

const route = useRoute();
const router = useRouter();
const exam = ref(null);
const loading = ref(true);
const submitting = ref(false);

const studentId = ref(2); // ID de aluno padrão para simulação
const answers = ref({});

onMounted(async () => {
  try {
    const response = await api.get(`/exams/${route.params.id}`);
    exam.value = response.data;
  } catch (error) {
    alert('Erro ao carregar a prova.');
  } finally {
    loading.value = false;
  }
});

const submitExam = async () => {
  submitting.value = true;
  try {
    const payload = {
      student_id: studentId.value,
      answers: Object.entries(answers.value).map(([qId, optId]) => ({
        question_id: parseInt(qId),
        option_id: parseInt(optId)
      }))
    };

    const response = await api.post(`/exams/${exam.value.id}/submit`, payload);
    
    // Armazena resultado no estado de navegação e redireciona
    router.push({
      name: 'student.exams.result',
      params: { id: exam.value.id },
      state: { result: response.data }
    });
  } catch (error) {
    const msg = error.response?.data?.message || 'Erro ao submeter prova.';
    alert(msg);
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.user-id-box { background: #f1f5f9; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; }
.question-card { border: 1px solid #cbd5e1; padding: 1.25rem; border-radius: 6px; margin-bottom: 1rem; }
.option-item { margin: 0.5rem 0; }
.btn-submit { background: #16a34a; color: white; border: none; padding: 0.75rem 1.5rem; font-size: 1rem; border-radius: 6px; cursor: pointer; }
.btn-submit:disabled { opacity: 0.6; }
</style>