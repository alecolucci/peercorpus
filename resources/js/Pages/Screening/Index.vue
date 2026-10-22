<script setup>
import { onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    article: Object, // O artigo atual
    progress: Object,
});

// Função para enviar a decisão
const makeDecision = (status) => {
    if (!props.article) return;
    
    router.patch(route('screening.update', [props.project.id, props.article.id]), {
        status: status
    }, {
        preserveScroll: false,
        preserveState: false,
    });
};

// Atalhos de teclado (Opcional, mas pesquisadores amam)
const handleKeydown = (e) => {
    if (e.key === 'ArrowRight' || e.key === 'i') makeDecision('included');
    if (e.key === 'ArrowLeft' || e.key === 'e') makeDecision('excluded');
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    
    // Escuta o canal do projeto
    window.Echo.private(`project.${props.project.id}`)
        .listen('ArticleScreened', (event) => {
            // Se o artigo que acabou de ser triado pelo colega for o mesmo que está na minha tela...
            if (props.article && event.articleId === props.article.id) {
                showToast(`O artigo atual foi avaliado por ${event.userName}. Carregando o próximo...`);
                // Força o recarregamento silencioso para puxar um artigo novo
                router.reload({ only: ['article', 'progress'], preserveState: false, preserveScroll: true });
            }
        });
});

onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Head title="Triagem de Artigos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Triagem: {{ project.title }}
                </h2>
                <Link :href="route('projects.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Voltar aos Projetos
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Barra de Progresso -->
                <div class="bg-white p-4 shadow sm:rounded-lg">
                    <div class="flex justify-between text-sm font-medium text-gray-600 mb-2">
                        <span>Progresso da Triagem</span>
                        <span>{{ progress.screened }} / {{ progress.total }} ({{ progress.percentage }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="`width: ${progress.percentage}%`"></div>
                    </div>
                </div>

                <!-- Cartão do Artigo -->
                <div v-if="article" class="bg-white p-8 shadow sm:rounded-lg relative">
                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-2xl font-bold text-gray-900 leading-snug">{{ article.title }}</h3>
                        <p class="text-gray-600 mt-2 text-sm">
                            <span class="font-semibold">Autores:</span> {{ article.authors || 'Não informado' }} <br>
                            <span class="font-semibold">Ano:</span> {{ article.publication_year || 'N/A' }} | 
                            <span class="font-semibold">DOI:</span> 
                            <a v-if="article.doi" :href="`https://doi.org/${article.doi}`" target="_blank" class="text-blue-600 hover:underline">{{ article.doi }}</a>
                            <span v-else>N/A</span>
                        </p>
                    </div>

                    <div class="prose max-w-none text-gray-800 text-lg leading-relaxed mb-8">
                        <h4 class="text-sm uppercase tracking-widest text-gray-500 font-bold mb-2">Resumo (Abstract)</h4>
                        <p v-if="article.abstract">{{ article.abstract }}</p>
                        <p v-else class="italic text-gray-400">Nenhum resumo fornecido para este artigo.</p>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex justify-center gap-6 mt-8">
                        <button @click="makeDecision('excluded')" class="flex-1 max-w-xs bg-red-100 text-red-700 hover:bg-red-200 hover:text-red-800 font-bold py-4 rounded-xl shadow-sm transition flex items-center justify-center gap-2 text-lg">
                            <span>&times;</span> Rejeitar (Esquerda)
                        </button>
                        <button @click="makeDecision('included')" class="flex-1 max-w-xs bg-green-100 text-green-700 hover:bg-green-200 hover:text-green-800 font-bold py-4 rounded-xl shadow-sm transition flex items-center justify-center gap-2 text-lg">
                            <span>&check;</span> Aceitar (Direita)
                        </button>
                    </div>
                </div>

                <!-- Estado de Conclusão -->
                <div v-else class="bg-white p-12 shadow sm:rounded-lg text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Triagem Concluída!</h3>
                    <p class="text-gray-600 mt-2">Você avaliou todos os artigos deste projeto.</p>
                    <Link :href="route('projects.index')" class="mt-6 inline-block bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700">
                        Voltar ao Dashboard
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>