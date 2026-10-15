<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div v-if="loading" class="p-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">
      Carregando questões da prova...
    </div>

    <div v-else-if="error" class="p-4 bg-rose-50 text-rose-700 rounded-xl border border-rose-200 text-sm">
      {{ error }}
    </div>

    <template v-else-if="exam">
      <header class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-2">
        <h1 class="text-2xl font-bold text-slate-800">{{ exam.title }}</h1>
        <p class="text-slate-500 text-sm">{{ exam.description || 'Responda com atenção a todas as questões abaixo.' }}</p>
      </header>

      <form @submit.prevent="handleFormSubmit" class="space-y-6">
        <div 
          v-for="(question, qIndex) in exam.questions" 
          :key="question.id" 
          class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4"
        >
          <div class="flex justify-between items-start gap-4">
            <h2 class="text-base font-semibold text-slate-800">
              <span class="text-indigo-600 font-bold mr-1">Q{{ qIndex + 1 }}.</span> {{ question.statement }}
            </h2>
            <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md whitespace-nowrap">
              {{ question.points }} pts
            </span>
          </div>

          <div class="space-y-2 pt-2">
            <label 
              v-for="option in question.options" 
              :key="option.id" 
              class="flex items-center gap-3 p-3.5 rounded-lg border border-slate-200/80 cursor-pointer hover:bg-slate-50 transition"
              :class="{ 'border-indigo-500 bg-indigo-50/30': answers[question.id] === option.id }"
            >
              <input 
                type="radio" 
                :name="`question_${question.id}`" 
                :value="option.id" 
                v-model="answers[question.id]" 
                required 
                class="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
              />
              <span class="text-sm text-slate-700">{{ option.text }}</span>
            </label>
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="submitting" 
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition disabled:opacity-50 text-base"
        >
          {{ submitting ? 'Finalizando e Enviando...' : 'Finalizar e Enviar Prova' }}
        </button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useExams } from '../../components/useExams';

const route = useRoute();
const router = useRouter();
const { exam, loading, submitting, error, fetchExamById, submitExam } = useExams();

const answers = ref({});

onMounted(async () => {
  await fetchExamById(route.params.id);
});

const handleFormSubmit = async () => {
  try {
    const payload = {
      student_id: 2, //id fixo
      answers: answers.value
    };

    const response = await submitExam(route.params.id, payload);

    router.push({
      name: 'student.exams.result',
      params: { id: route.params.id },
      state: {
        result: {
          score: response.score ?? response.attempt?.score ?? 0,
          total_points: response.total_points ?? response.attempt?.total_points ?? 0,
          percentage: response.percentage ?? response.attempt?.percentage ?? 0
        }
      }
    });
  } catch (err) {
    alert(err.response?.data?.message || 'Erro ao enviar a prova.');
  }
};
</script>