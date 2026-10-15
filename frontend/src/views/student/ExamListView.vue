<template>
  <div class="space-y-6">
    <header class="border-b border-slate-200 pb-4">
      <h1 class="text-2xl font-bold text-slate-800">📝 Provas Disponíveis</h1>
      <p class="text-slate-500 text-sm">Selecione uma avaliação para responder e testar seus conhecimentos</p>
    </header>

    <div v-if="loading" class="p-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">
      Carregando provas disponíveis...
    </div>

    <div v-else-if="error" class="p-4 bg-rose-50 text-rose-700 rounded-xl border border-rose-200 text-sm">
      {{ error }}
    </div>

    <div v-else-if="exams.length === 0" class="p-12 text-center bg-white rounded-xl border border-slate-200 text-slate-500">
      Nenhuma prova cadastrada ou disponível no momento.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="item in exams" 
        :key="item.id" 
        class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-all"
      >
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
              #{{ item.id }}
            </span>
            <span class="text-xs text-slate-400">
              {{ item.questions_count ?? item.questions?.length ?? 0 }} Questões
            </span>
          </div>

          <h2 class="text-lg font-bold text-slate-800 leading-snug">{{ item.title }}</h2>
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
</template>

<script setup>
import { onMounted } from 'vue';
import { useExams } from '../../components/useExams';

const { exams, loading, error, fetchExams } = useExams();

onMounted(fetchExams);
</script>