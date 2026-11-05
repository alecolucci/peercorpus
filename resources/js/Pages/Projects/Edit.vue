<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Editar Revisão: {{ project.title }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-200">
          
          <form @submit.prevent="submit" class="space-y-6">
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">Título do Projeto</label>
              <input 
                id="title" 
                v-model="form.title" 
                type="text" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                required
              />
              <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
            </div>

            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Descrição / Pergunta de Pesquisa</label>
              <textarea 
                id="description" 
                v-model="form.description" 
                rows="4" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              ></textarea>
            </div>

            <div class="flex items-center justify-end gap-4">
              <Link :href="route('projects.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
              >
                Atualizar Projeto
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

// Recebe os dados do projeto enviados pelo controller
const props = defineProps({
  project: {
    type: Object,
    required: true,
  }
});

// Pré-preenche o formulário com os dados existentes
const form = useForm({
  title: props.project.title,
  description: props.project.description,
});

// Envia a requisição como PUT para a rota de update
const submit = () => {
  form.put(route('projects.update', props.project.id));
};
</script>