<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    articles: Array,
    tags: Array,
    appliedTags: Array,
});

// Estado local
const selectedArticle = ref(props.articles.length > 0 ? props.articles[0] : null);

// Formulário para Nova Tag
const formTag = useForm({
    name: '',
    color: '#4F46E5', // Indigo padrão
});

const createTag = () => {
    formTag.post(route('tags.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => formTag.reset('name'),
    });
};

// Computa as tags aplicadas APENAS ao artigo atualmente selecionado
const currentArticleTags = computed(() => {
    if (!selectedArticle.value) return [];
    
    // Filtra os IDs das tags ligadas a este artigo
    const appliedIds = props.appliedTags
        .filter(at => at.article_id === selectedArticle.value.id)
        .map(at => at.tag_id);
        
    // Retorna os objetos completos das tags para podermos usar os nomes e cores
    return props.tags.filter(t => appliedIds.includes(t.id));
});

// Ações de Tag (API)
const attachTag = (tagId) => {
    if (!selectedArticle.value) return;
    // Evita duplicados na interface se o usuário clicar/arrastar duas vezes
    if (currentArticleTags.value.find(t => t.id === tagId)) return; 

    router.post(route('tags.attach', selectedArticle.value.id), { tag_id: tagId }, { preserveScroll: true });
};

const detachTag = (tagId) => {
    if (!selectedArticle.value) return;
    router.post(route('tags.detach', selectedArticle.value.id), { tag_id: tagId }, { preserveScroll: true });
};

// HTML5 Drag and Drop Handlers
const onDragStart = (event, tagId) => {
    event.dataTransfer.dropEffect = 'copy';
    event.dataTransfer.setData('tagId', tagId);
};

const onDrop = (event) => {
    const tagId = parseInt(event.dataTransfer.getData('tagId'));
    if (tagId) attachTag(tagId);
};
</script>

<template>
    <Head title="Workspace de Codificação" />

    <AuthenticatedLayout>
        <!-- Layout Flexbox de altura total (Full-height) -->
        <div class="flex h-[calc(100vh-65px)] overflow-hidden bg-gray-50">
            
            <!-- COLUNA 1: Lista de Artigos (Esquerda) -->
            <div class="w-1/4 min-w-[250px] bg-white border-r border-gray-200 flex flex-col">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-700">Artigos Incluídos</h3>
                    <Link :href="route('projects.index')" class="text-xs text-indigo-600 hover:underline">Sair</Link>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="article in articles" :key="article.id">
                            <button 
                                @click="selectedArticle = article"
                                :class="['w-full text-left p-4 hover:bg-indigo-50 transition', selectedArticle?.id === article.id ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'border-l-4 border-transparent']"
                            >
                                <h4 class="text-sm font-bold text-gray-900 line-clamp-2">{{ article.title }}</h4>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ article.authors }}</p>
                            </button>
                        </li>
                    </ul>
                    <div v-if="articles.length === 0" class="p-4 text-sm text-gray-500 italic">
                        Nenhum artigo aprovado na triagem ainda.
                    </div>
                </div>
            </div>

            <!-- COLUNA 2: Área de Leitura e Dropzone (Centro) -->
            <div class="flex-1 flex flex-col bg-gray-50">
                <div v-if="selectedArticle" class="flex-1 flex flex-col relative h-full">
                    
                    <!-- Cabeçalho do Artigo & Tags Aplicadas -->
                    <div class="bg-white p-6 border-b border-gray-200 shadow-sm z-10">
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight mb-2">{{ selectedArticle.title }}</h2>
                        <div class="text-sm text-gray-600 mb-4 flex gap-4">
                            <span><strong class="font-medium">Autores:</strong> {{ selectedArticle.authors }}</span>
                            <span><strong class="font-medium">Ano:</strong> {{ selectedArticle.publication_year }}</span>
                        </div>

                        <!-- Zona de Tags Aplicadas (Pills) -->
                        <div class="flex flex-wrap gap-2 items-center min-h-[32px]">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider mr-2">Códigos:</span>
                            
                            <span v-for="tag in currentArticleTags" :key="tag.id" 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border shadow-sm transition-all"
                                :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color + '40' }">
                                {{ tag.name }}
                                <button @click="detachTag(tag.id)" class="ml-1.5 hover:text-red-600 focus:outline-none">
                                    &times;
                                </button>
                            </span>
                            
                            <span v-if="currentArticleTags.length === 0" class="text-sm text-gray-400 italic">
                                Arraste tags para cá ou clique nelas no menu lateral.
                            </span>
                        </div>
                    </div>

                    <!-- Área de Texto com Dropzone HTML5 -->
                    <div class="flex-1 overflow-y-auto p-8 relative group" 
                         @dragover.prevent 
                         @drop="onDrop">
                        
                        <!-- Dropzone Overlay Visual (Aparece ao arrastar) -->
                        <div class="absolute inset-4 border-2 border-dashed border-indigo-300 rounded-lg bg-indigo-50/50 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity flex items-center justify-center">
                            <span class="text-indigo-500 font-bold text-xl bg-white/80 px-4 py-2 rounded-lg backdrop-blur-sm">
                                Solte a tag aqui para codificar
                            </span>
                        </div>

                        <div class="prose max-w-3xl mx-auto relative z-10 pointer-events-auto">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Abstract</h3>
                            <p class="text-gray-700 leading-relaxed text-lg whitespace-pre-line">
                                {{ selectedArticle.abstract || 'Resumo não disponível.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="flex-1 flex items-center justify-center text-gray-500">
                    Selecione um artigo na lista à esquerda.
                </div>
            </div>

            <!-- COLUNA 3: Codebook / Tags (Direita) -->
            <div class="w-1/4 min-w-[260px] bg-white border-l border-gray-200 flex flex-col shadow-[-4px_0_15px_-3px_rgba(0,0,0,0.05)] z-20">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Codebook
                    </h3>
                </div>

                <!-- Formulário para criar Tag -->
                <div class="p-4 border-b border-gray-100 bg-white">
                    <form @submit.prevent="createTag" class="flex gap-2">
                        <input type="color" v-model="formTag.color" class="w-10 h-10 p-1 border border-gray-300 rounded cursor-pointer shrink-0">
                        <div class="flex-1 flex">
                            <input type="text" v-model="formTag.name" placeholder="Novo código..." required
                                class="w-full border-gray-300 rounded-l-md focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <button type="submit" :disabled="formTag.processing" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 rounded-r-md font-bold transition">
                                +
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Tags Arrastáveis -->
                <div class="flex-1 overflow-y-auto p-4">
                    <p class="text-xs text-gray-400 mb-4 font-semibold uppercase tracking-wider">Arraste para o texto</p>
                    
                    <div class="space-y-2">
                        <div v-for="tag in tags" :key="tag.id" 
                            draggable="true" 
                            @dragstart="onDragStart($event, tag.id)"
                            class="group flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab active:cursor-grabbing hover:border-indigo-300 hover:shadow transition"
                        >
                            <div class="flex items-center gap-3 overflow-hidden">
                                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: tag.color }"></span>
                                <span class="text-sm font-medium text-gray-700 truncate">{{ tag.name }}</span>
                            </div>
                            
                            <!-- Botão alternativo para quem preferir clicar em vez de arrastar -->
                            <button @click="attachTag(tag.id)" class="text-gray-400 hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition focus:outline-none" title="Aplicar ao artigo atual">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                        
                        <div v-if="tags.length === 0" class="text-sm text-gray-500 text-center py-4 border-2 border-dashed border-gray-200 rounded-lg">
                            Seu codebook está vazio. <br>Crie a primeira tag acima.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>