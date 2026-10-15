<template>
  <div class="space-y-6">
    <header class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">⚙️ Gerenciar Provas Cadastradas</h1>
        <p class="text-slate-500 text-sm">Listagem completa e ações administrativas de exclusão</p>
      </div>
      <router-link 
        to="/professor/exams/create" 
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg transition"
      >
        + Nova Prova
      </router-link>
    </header>

    <!-- State de Loading -->
    <div v-if="loading" class="p-8 text-center text-slate-500 bg-white rounded-xl border border-slate-200">
      Carregando lista de provas...
    </div>

    <!-- State Vazio -->
    <div v-else-if="exams.length === 0" class="p-12 text-center bg-white rounded-xl border border-slate-200 space-y-3">
      <p class="text-slate-500 font-medium">Nenhuma prova encontrada no sistema.</p>
      <router-link to="/professor/exams/create" class="inline-block text-indigo-600 hover:underline font-semibold text-sm">
        Criar a primeira prova
      </router-link>
    </div>

    <!-- Tabela de Gerenciamento -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 font-medium border-b border-slate-200/80">
          <tr>
            <th class="px-6 py-4">ID</th>
            <th class="px-6 py-4">Título</th>
            <th class="px-6 py-4">Descrição</th>
            <th class="px-6 py-4 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="exam in exams" :key="exam.id" class="hover:bg-slate-50/80 transition-colors">
            <td class="px-6 py-4 font-mono font-semibold text-slate-400">#{{ exam.id }}</td>
            <td class="px-6 py-4 font-semibold text-slate-800">{{ exam.title }}</td>
            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">{{ exam.description || 'Sem descrição' }}</td>
            <td class="px-6 py-4 text-right">
              <button 
                @click="handleDelete(exam.id)" 
                class="inline-flex items-center px-3 py-1.5 border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg text-xs font-semibold transition"
              >
                Excluir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useExams } from '../../components/useExams';

const { exams, loading, fetchExams, deleteExam } = useExams();

onMounted(fetchExams);

const handleDelete = async (id) => {
  if (!confirm('Tem certeza de que deseja excluir esta prova? Esta ação não pode ser desfeita.')) return;
  
  try {
    await deleteExam(id);
    alert('Prova excluída com sucesso!');
  } catch (err) {
    alert('Erro ao excluir a prova.');
  }
};
</script>