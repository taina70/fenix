<template>
  <div class="max-w-xl mx-auto text-center py-12 space-y-8">
    <div v-if="loading" class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-md">
      <p class="text-slate-500">Buscando resultado da avaliação...</p>
    </div>

    <div v-else-if="result" class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-md space-y-6">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full text-3xl">
        🎉
      </div>

      <div class="space-y-2">
        <h1 class="text-2xl font-bold text-slate-800">Prova Submetida com Sucesso!</h1>
        <p class="text-slate-500 text-sm">Sua correção foi efetuada automaticamente pelo sistema.</p>
      </div>

      <div class="grid grid-cols-2 gap-4 bg-slate-50 p-6 rounded-xl border border-slate-100">
        <div class="space-y-1">
          <span class="block text-xs font-semibold uppercase text-slate-400">Pontuação</span>
          <span class="text-3xl font-bold text-slate-800">{{ result.score ?? 0 }} / {{ result.total_points ?? 0 }}</span>
        </div>

        <div class="space-y-1">
          <span class="block text-xs font-semibold uppercase text-slate-400">Aproveitamento</span>
          <span class="text-3xl font-bold text-emerald-600">{{ result.percentage ?? 0 }}%</span>
        </div>
      </div>

      <div class="flex gap-3 justify-center">
        <router-link 
          to="/student/exams" 
          class="inline-block bg-slate-800 hover:bg-slate-900 text-white font-medium px-6 py-3 rounded-xl transition"
        >
          Voltar para Provas
        </router-link>
        <router-link 
          to="/professor/dashboard" 
          class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-3 rounded-xl transition"
        >
          Ir para o Dashboard
        </router-link>
      </div>
    </div>

    <div v-else class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <p class="text-slate-500">Nenhum resultado recente encontrado para exibição.</p>
      <router-link 
        to="/student/exams" 
        class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2.5 rounded-lg transition text-sm"
      >
        Ver Provas Disponíveis
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/axios';

const route = useRoute();
const result = ref(null);
const loading = ref(false);

onMounted(async () => {
  // 1. Tenta recuperar do estado da navegação
  if (history.state && history.state.result) {
    result.value = history.state.result;
    return;
  }

  // 2. Fallback: Se não estiver no history.state, busca o resultado via API
  if (route.params.id) {
    loading.value = true;
    try {
      const response = await api.get(`/exams/${route.params.id}/result`);
      result.value = response.data.data || response.data;
    } catch (err) {
      console.error("Erro ao carregar resultado:", err);
    } finally {
      loading.value = false;
    }
  }
});
</script>