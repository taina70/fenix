<template>
  <div class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
      <span class="font-bold text-slate-700">Questão #{{ index + 1 }}</span>
      <button 
        v-if="canRemove" 
        type="button" 
        @click="$emit('remove')" 
        class="text-rose-600 hover:text-rose-800 text-xs font-semibold uppercase tracking-wider transition"
      >
        Remover Questão
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-slate-700 mb-1">Enunciado *</label>
        <input 
          type="text" 
          v-model="question.statement" 
          required 
          placeholder="Qual o resultado da expressão...?" 
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Pontos *</label>
        <input 
          type="number" 
          v-model.number="question.points" 
          min="1" 
          required 
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
    </div>

    <!-- Alternativas -->
    <div class="space-y-2 pt-2">
      <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">
        Opções de Resposta (Marque a correta)
      </label>

      <div 
        v-for="(option, oIndex) in question.options" 
        :key="option.id" 
        class="flex items-center gap-3 bg-slate-50/80 p-2.5 rounded-lg border border-slate-200/60"
      >
        <input 
          type="radio" 
          :name="`correct_${question.id}`" 
          :checked="option.is_correct" 
          @change="setCorrectOption(oIndex)" 
          required 
          class="w-4 h-4 text-emerald-600 focus:ring-emerald-500"
        />
        <input 
          type="text" 
          v-model="option.text" 
          required 
          placeholder="Texto da alternativa" 
          class="flex-1 px-3 py-1.5 border border-slate-300 rounded-md focus:ring-2 focus:ring-indigo-500 outline-none text-sm"
        />
        <button 
          v-if="question.options.length > 2" 
          type="button" 
          @click="removeOption(oIndex)" 
          class="text-slate-400 hover:text-rose-600 p-1 transition"
        >
          ✕
        </button>
      </div>

      <button 
        type="button" 
        @click="addOption" 
        class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold transition inline-block pt-1"
      >
        + Adicionar Alternativa
      </button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  question: { type: Object, required: true },
  index: { type: Number, required: true },
  canRemove: { type: Boolean, default: true }
});

defineEmits(['remove']);

const addOption = () => {
  props.question.options.push({
    id: crypto.randomUUID(),
    text: '',
    is_correct: false
  });
};

const removeOption = (oIndex) => {
  props.question.options.splice(oIndex, 1);
};

const setCorrectOption = (correctIndex) => {
  props.question.options.forEach((opt, idx) => {
    opt.is_correct = idx === correctIndex;
  });
};
</script>