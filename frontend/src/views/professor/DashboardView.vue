<template>
  <div class="space-y-6">
    <header class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">📊 Painel de Desempenho</h1>
        <p class="text-slate-500 text-sm">Métricas em tempo real atualizadas via Redis</p>
      </div>
    </header>

    <div v-if="loading" class="text-slate-500 p-4">Carregando dados...</div>

    <template v-else-if="stats">
      <!-- Grid de Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-indigo-500">
          <span class="text-xs font-semibold text-slate-400 uppercase">Média Geral</span>
          <span class="block text-2xl font-bold text-indigo-600 mt-2">{{ stats.average_score ?? 0 }} pts</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-emerald-500">
          <span class="text-xs font-semibold text-slate-400 uppercase">Maior Nota (Top 1)</span>
          <span class="block text-2xl font-bold text-emerald-600 mt-2">
            {{ typeof stats.top_score === 'object' ? (stats.top_score?.score ?? 0) : (stats.top_score ?? 0) }} pts
          </span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-rose-500">
          <span class="text-xs font-semibold text-slate-400 uppercase">Menor Nota</span>
          <span class="block text-2xl font-bold text-rose-600 mt-2">{{ stats.lowest_score ?? 0 }} pts</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-amber-500">
          <span class="text-xs font-semibold text-slate-400 uppercase">Total de Submissões</span>
          <span class="block text-2xl font-bold text-slate-700 mt-2">{{ stats.total_attempts ?? 0 }}</span>
        </div>
      </div>

      <!-- Tabela de Ranking -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden mt-6">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
          <h2 class="font-semibold text-slate-700">🏆 Ranking dos Alunos</h2>
        </div>

        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 font-medium">
            <tr>
              <th class="px-4 py-3">Posição</th>
              <th class="px-4 py-3">Aluno</th>
              <th class="px-4 py-3">Pontuação</th>
              <th class="px-4 py-3 text-right">Aproveitamento</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(item, index) in rankingList" :key="index" class="hover:bg-slate-50/80">
              <td class="px-4 py-3 font-semibold text-slate-400">#{{ index + 1 }}</td>
              <td class="px-4 py-3 font-medium text-slate-800">
                {{ item.student_name || item.student?.name || ('Aluno #' + (item.student_id || 'N/A')) }}
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.score ?? 0 }} pts</td>
              <td class="px-4 py-3 text-right">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ item.percentage ?? 0 }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useDashboard } from '../../components/useDashboard';

const { stats, loading, fetchStats } = useDashboard();

const rankingList = computed(() => {
  if (!stats.value?.ranking) return [];
  return Array.isArray(stats.value.ranking) 
    ? stats.value.ranking 
    : (stats.value.ranking.data || []);
});

onMounted(() => {
  fetchStats();
});
</script>