<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Meus Projetos (Revisões)</h2>
        <Link :href="route('projects.create')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
          + Novo Projeto
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div v-if="projects.length === 0" class="bg-white p-8 text-center rounded-lg shadow-sm border border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Nenhum projeto encontrado</h3>
          <p class="mt-1 text-sm text-gray-500">Comece criando sua primeira revisão sistemática.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="project in projects" :key="project.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
              <h3 class="text-lg font-bold text-gray-900 truncate" :title="project.title">
                {{ project.title }}
              </h3>
              <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 uppercase">
                {{ project.status }}
              </span>
            </div>
            
            <p class="text-sm text-gray-600 mb-4 line-clamp-2 flex-grow">
              {{ project.description || 'Sem descrição.' }}
            </p>
            
            <div class="flex items-center text-sm text-gray-500 mb-4">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              {{ project.articles_count }} artigos
            </div>

            <div class="flex gap-2 mt-auto pt-4 border-t border-gray-100">
              <Link :href="route('projects.show', project.id)" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex-1 text-center">
                Abrir Painel
              </Link>
              <Link :href="route('projects.edit', project.id)" class="text-gray-500 hover:text-gray-700 text-sm font-medium px-2">
                Editar
              </Link>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  projects: Array,
});
</script>