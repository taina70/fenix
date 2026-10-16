
<template>
  <div class="space-y-6">
    <header
      class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center border-b border-slate-200 pb-4"
    >
      <div>
        <h1 class="text-2xl font-bold text-slate-800">
          📝 Provas do Aluno
        </h1>
        <p class="text-slate-500 text-sm">
          Responda às avaliações pendentes ou consulte suas notas
        </p>
      </div>

      <div class="flex gap-2 bg-slate-100 p-1 rounded-xl">
        <button
          @click="activeTab = 'pending'"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition',
            activeTab === 'pending'
              ? 'bg-white text-slate-800 shadow-sm'
              : 'text-slate-500 hover:text-slate-700'
          ]"
        >
          Pendentes ({{ pendingExams.length }})
        </button>

        <button
          @click="activeTab = 'completed'"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition',
            activeTab === 'completed'
              ? 'bg-white text-slate-800 shadow-sm'
              : 'text-slate-500 hover:text-slate-700'
          ]"
        >
          Concluídas ({{ completedExams.length }})
        </button>
      </div>
    </header>

    <div
      v-if="loading"
      class="p-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200"
    >
      Carregando provas...
    </div>

    <div
      v-else-if="error"
      class="p-4 bg-rose-50 text-rose-700 rounded-xl border border-rose-200 text-sm"
    >
      {{ error }}
    </div>

    <!-- ABA DE PROVAS PENDENTES -->
    <div v-else-if="activeTab === 'pending'">
      <div
        v-if="pendingExams.length === 0"
        class="p-12 text-center bg-white rounded-xl border border-slate-200 text-slate-500"
      >
        🎉 Nenhuma prova pendente! Você respondeu todas as provas disponíveis.
      </div>

      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="item in pendingExams"
          :key="item.id"
          class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-all"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100"
              >
                #{{ item.id }}
              </span>

              <span class="text-xs text-slate-400">
                {{ item.questions_count ?? item.questions?.length ?? 0 }}
                Questões
              </span>
            </div>

            <h2 class="text-lg font-bold text-slate-800 leading-snug">
              {{ item.title }}
            </h2>

            <p class="text-slate-500 text-sm line-clamp-2">
              {{ item.description || 'Sem descrição cadastrada para esta prova.' }}
            </p>
          </div>

          <div class="pt-6 mt-4 border-t border-slate-100">
            <router-link
              :to="`/student/exams/${item.id}`"
              class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg transition shadow-sm text-sm"
            >
              Iniciar Prova
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- ABA DE PROVAS CONCLUÍDAS -->
    <div v-else-if="activeTab === 'completed'">
      <div
        v-if="completedExams.length === 0"
        class="p-12 text-center bg-white rounded-xl border border-slate-200 text-slate-500"
      >
        Nenhuma prova concluída até o momento.
      </div>

      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="item in completedExams"
          :key="item.id"
          class="bg-slate-50 p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"
              >
                Concluída
              </span>

              <span class="text-xs text-slate-400">
                #{{ item.exam?.id ?? item.exam_id }}
              </span>
            </div>

            <h2 class="text-lg font-bold text-slate-800 leading-snug">
              {{ item.exam?.title ?? 'Prova' }}
            </h2>

            <p class="text-slate-500 text-sm line-clamp-2">
              {{ item.exam?.description || 'Avaliação finalizada.' }}
            </p>
          </div>

          <div
            class="pt-4 mt-4 border-t border-slate-200 bg-white p-3 rounded-lg"
          >
            <div class="flex justify-between items-center gap-3">
              <span class="text-xs font-semibold text-slate-500 uppercase">
                Sua Nota
              </span>

              <span class="text-lg font-bold text-emerald-600">
                {{ item.score }} / {{ item.total_points }} pts
              </span>
            </div>

            <div class="mt-3 space-y-2 text-sm text-slate-500">
              <p>
                Aproveitamento:
                <strong class="text-slate-700">
                  {{ formatPercentage(item.percentage) }}%
                </strong>
              </p>

              <p>
                Concluída em:
                <strong class="text-slate-700">
                  {{ formatDate(item.completed_at) }}
                </strong>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useExams } from '../../components/useExams';
import { useAuth } from '../../components/useAuth';

const activeTab = ref('pending');
const completedExams = ref([]);

const {
  exams,
  loading,
  error,
  fetchExams,
  fetchStudentHistory
} = useExams();

const { currentUser } = useAuth();

const loadStudentExams = async () => {
  completedExams.value = [];

  try {
    await fetchExams({ student_id: currentUser.value.id });

    completedExams.value = await fetchStudentHistory(
      currentUser.value.id
    );
  } catch (err) {
    console.error('Erro ao carregar provas do aluno:', err);
  }
};

const pendingExams = computed(() => {
  return exams.value.filter(exam => !exam.has_submitted);
});

const formatPercentage = (percentage) => {
  const value = Number(percentage);
  return Number.isFinite(value) ? value.toFixed(1) : '0.0';
};

const formatDate = (date) => {
  if (!date) {
    return 'Data indisponível';
  }

  const normalizedDate = date.includes('T')
    ? date
    : date.replace(' ', 'T');

  const parsedDate = new Date(normalizedDate);

  if (Number.isNaN(parsedDate.getTime())) {
    return 'Data indisponível';
  }

  return parsedDate.toLocaleString('pt-BR');
};

onMounted(loadStudentExams);

watch(
  () => currentUser.value.id,
  loadStudentExams
);
</script>
