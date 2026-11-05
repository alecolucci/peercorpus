<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ project.title }}</h2>
          <p class="text-sm text-gray-500 mt-1">Status: {{ project.status }}</p>
        </div>
        
        <button @click="deleteProject" class="text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-md text-sm font-medium transition">
          Excluir Projeto
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Coluna Principal -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- Visão Geral (Agora dinâmica) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 mb-2">Visão Geral</h3>
              <p class="text-gray-600 mb-6">{{ project.description || 'Nenhuma descrição fornecida.' }}</p>
              
              <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-6">
                <div class="text-center">
                  <span class="block text-2xl font-bold text-blue-600">{{ project.articles_count || 0 }}</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Importados</span>
                </div>
                <div class="text-center border-l border-gray-100">
                  <span class="block text-2xl font-bold text-indigo-600">{{ triagedCount }}</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Triados</span>
                </div>
                <div class="text-center border-l border-gray-100">
                  <span class="block text-2xl font-bold text-gray-400">0</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Codificados</span>
                </div>
              </div>
            </div>

            <!-- Widget de Triagem (Screening) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Módulo de Triagem (Screening)</h3>
                  <p class="text-sm text-gray-500 mt-1">Leitura de Títulos e Resumos para aprovação.</p>
                </div>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Etapa 1</span>
              </div>

              <!-- Barra de Progresso da Triagem -->
              <div class="my-6 bg-gray-50 p-4 rounded-md border border-gray-100">
                <div class="flex justify-between text-sm mb-2">
                  <span class="font-medium text-gray-700">Progresso da Equipe</span>
                  <span class="text-gray-500">{{ triageProgress }}% concluído</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-3">
                  <div class="bg-indigo-600 h-2.5 rounded-full transition-all" :style="{ width: triageProgress + '%' }"></div>
                </div>
                
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                  <span>Restam: <strong>{{ project.pending_count || 0 }}</strong> pendentes</span>
                  <span class="text-green-600"><strong>{{ project.included_count || 0 }}</strong> aceitos</span>
                  <span class="text-red-600"><strong>{{ project.excluded_count || 0 }}</strong> rejeitados</span>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <Link 
                  :href="route('projects.screening', project.id)" 
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition focus:ring-2 focus:ring-indigo-500"
                  :class="{'opacity-50 pointer-events-none': !project.articles_count}"
                >
                  {{ project.pending_count > 0 ? 'Iniciar Triagem ➔' : 'Acessar Revisões ➔' }}
                </Link>
              </div>
            </div>

          </div>

          <!-- Coluna Lateral: Importador -->
          <div class="lg:col-span-1">
            <ArticleImporter :project-id="project.id" />
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ArticleImporter from '@/Components/ArticleImporter.vue';

const props = defineProps({
  project: Object,
});

// Calcula o total de artigos que já passaram pela decisão (Aceito ou Rejeitado)
const triagedCount = computed(() => {
  return (props.project.included_count || 0) + (props.project.excluded_count || 0);
});

// Calcula a porcentagem para a barra de progresso da Triagem
const triageProgress = computed(() => {
  const total = props.project.articles_count || 0;
  if (total === 0) return 0;
  return Math.round((triagedCount.value / total) * 100);
});

const deleteProject = () => {
  if (confirm('Tem certeza que deseja excluir esta revisão e TODOS os artigos importados? Esta ação é irreversível.')) {
    router.delete(route('projects.destroy', props.project.id));
  }
};
</script>
