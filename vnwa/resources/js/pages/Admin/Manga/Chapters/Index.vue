<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import axios from '@/axios';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import mangas from '@/routes/mangas';
import ChapterZipUploader from '@/components/ChapterZipUploader.vue';
import ChapterItem from '@/components/ChapterItem.vue';
import ChapterViewDialog from '@/components/ChapterViewDialog.vue';
import ChapterEditDialog from '@/components/ChapterEditDialog.vue';
import {
    Plus,
    Upload,
    Link as LinkIcon,
    FileArchive,
    ArrowUpDown,
    Search,
    X,
} from 'lucide-vue-next';

const toast = useToast();

interface MangaChapter {
    id: number;
    name: string;
    slug: string;
    description?: string;
    order: number;
    coin_cost?: number;
    is_locked?: boolean;
    created_at?: string;
    user?: {
        id: number;
        name: string;
    };
    server_contents?: Array<{
        id: number;
        manga_server_id?: number;
        urls: string[];
        server?: {
            id: number;
            name: string;
        };
    }>;
}

interface Props {
    manga: {
        id: number;
        name: string;
        slug: string;
        author?: {
            id: number;
            name: string;
        };
        badge?: {
            id: number;
            name: string;
        };
    };
    chapters: MangaChapter[];
    servers: Array<{
        id: number;
        name: string;
    }>;
    csrf_token?: string;
}

const props = defineProps<Props>();
const chapters = ref<MangaChapter[]>(props.chapters);
const isLoading = ref(false);
const isReordering = ref(false);
const searchQuery = ref('');

// Dialog states
const showUploadZipDialog = ref(false);
const showAddUrlsDialog = ref(false);
const showAddChapterDialog = ref(false);
const showEditDialog = ref(false);
const showViewDialog = ref(false);
const selectedChapter = ref<MangaChapter | null>(null);



// Breadcrumbs
const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Mangas',
        href: mangas.index().url,
    },
    {
        title: props.manga.name,
        href: mangas.show(props.manga.id).url,
    },
    {
        title: 'Chapters',
        href: '#',
    },
]);

// Filtered chapters
const filteredChapters = computed(() => {
    if (!searchQuery.value.trim()) {
        return chapters.value;
    }
    const query = searchQuery.value.toLowerCase();
    return chapters.value.filter(
        (chapter) =>
            chapter.name.toLowerCase().includes(query) ||
            chapter.description?.toLowerCase().includes(query) ||
            chapter.slug.toLowerCase().includes(query),
    );
});

// Drag and drop
const onDragStart = () => {
    isReordering.value = true;
};

const onDragEnd = async () => {
    isReordering.value = false;
    // Calculate order in reverse (DESC order: first item has highest order)
    const totalChapters = chapters.value.length;
    const reorderedChapters = chapters.value.map((chapter, index) => ({
        id: chapter.id,
        order: totalChapters - 1 - index,
    }));

    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.reorder(props.manga.id).url, {
            chapters: reorderedChapters,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            // Update local order (DESC: first item has highest order)
            const totalChapters = chapters.value.length;
            chapters.value.forEach((chapter, index) => {
                chapter.order = totalChapters - 1 - index;
            });
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi sắp xếp lại chapters.');
        // Revert order on error - reload from server
        router.reload({ only: ['chapters'] });
    } finally {
        isLoading.value = false;
    }
};

// FilePond handlers
const zipUploaderRef = ref<InstanceType<typeof ChapterZipUploader> | null>(null);

const handleChapterUploaded = (chapter: MangaChapter) => {
    chapters.value.push(chapter);
    chapters.value.sort((a, b) => b.order - a.order);
    toast.success(`Chapter "${chapter.name}" đã được upload thành công.`);
};

const handleAllChaptersComplete = (uploadedChapters: MangaChapter[]) => {
    toast.success(`Đã upload thành công ${uploadedChapters.length} chapter(s).`);
    showUploadZipDialog.value = false;
    zipUploaderRef.value?.clear();
};

const handleUploadError = (error: string) => {
    toast.error(error);
};

// Add from URLs
const handleAddFromUrls = async (data: { name: string; urls: string; description: string }) => {
    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.storeFromUrls(props.manga.id).url, {
            manga_id: props.manga.id,
            name: data.name,
            urls: data.urls,
            description: data.description,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value.push(response.data.chapter);
            chapters.value.sort((a, b) => b.order - a.order);
            showAddUrlsDialog.value = false;
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo chapter từ URLs.');
    } finally {
        isLoading.value = false;
    }
};

// Add chapter
const handleAddChapter = async (data: { name: string; description: string }) => {
    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.store(props.manga.id).url, {
            manga_id: props.manga.id,
            name: data.name,
            description: data.description,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value.push(response.data.chapter);
            chapters.value.sort((a, b) => b.order - a.order);
            showAddChapterDialog.value = false;
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo chapter.');
    } finally {
        isLoading.value = false;
    }
};

// Edit chapter
const openEditDialog = (chapter: MangaChapter) => {
    selectedChapter.value = chapter;
    showEditDialog.value = true;
};

const handleUpdateChapterSubmit = async (data: {
    name: string;
    description: string;
    coin_cost: number;
    is_locked: boolean;
    server_contents: Array<{
        id?: number;
        manga_server_id?: number;
        urls: string;
    }>;
}) => {
    if (!selectedChapter.value) {
        return;
    }

    if (!data.name.trim()) {
        toast.error('Vui lòng nhập tên chapter.');
        return;
    }

    try {
        isLoading.value = true;

        const response = await axios.put(mangas.chapters.update({ manga: props.manga.id, chapter: selectedChapter.value.id }).url, {
            name: data.name,
            description: data.description,
            coin_cost: data.coin_cost,
            is_locked: data.is_locked,
            server_contents: data.server_contents,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            const index = chapters.value.findIndex((c) => c.id === selectedChapter.value!.id);
            if (index !== -1) {
                chapters.value[index] = response.data.chapter;
            }
            showEditDialog.value = false;
            selectedChapter.value = null;
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật chapter.');
    } finally {
        isLoading.value = false;
    }
};

// View chapter details
const openViewDialog = (chapter: MangaChapter) => {
    selectedChapter.value = chapter;
    showViewDialog.value = true;
};

// Delete chapter
const handleDeleteChapter = async (chapterId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa chapter này? Hành động này không thể hoàn tác.')) {
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.delete(mangas.chapters.destroy({ manga: props.manga.id, chapter: chapterId }).url);

        if (response.data?.success) {
            toast.success(response.data.message || 'Chapter đã được xóa thành công.');
            chapters.value = chapters.value.filter((c) => c.id !== chapterId);
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa chapter.');
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>

    <Head :title="`Chapters - ${manga.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Chapters</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ chapters.length }} chapters
                        </Badge>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <p class="text-base">{{ manga.name }}</p>
                        <span v-if="manga.author" class="text-sm">• {{ manga.author.name }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <!-- Upload ZIP Dialog -->
                    <Dialog v-model:open="showUploadZipDialog">
                        <DialogTrigger as-child>
                            <Button variant="outline" class="gap-2">
                                <FileArchive class="h-4 w-4" />
                                <span class="hidden sm:inline">Upload ZIP</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[550px]">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2">
                                    <FileArchive class="h-5 w-5" />
                                    Upload Chapter từ ZIP
                                </DialogTitle>
                                <DialogDescription>
                                    Tải lên một hoặc nhiều file ZIP. Tên file ZIP sẽ được sử dụng làm tên chapter.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="flex flex-col gap-4 py-4">
                                <ChapterZipUploader ref="zipUploaderRef" :manga-id="manga.id" :max-files="10"
                                    :max-file-size="'500MB'" :csrf-token="csrf_token" @uploaded="handleChapterUploaded"
                                    @all-complete="handleAllChaptersComplete" @error="handleUploadError" />
                                <p class="text-xs text-muted-foreground">
                                    Hỗ trợ các định dạng ảnh: JPG, PNG, GIF, WEBP. Tối đa 500MB mỗi file.
                                </p>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showUploadZipDialog = false">
                                    Đóng
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Add from URLs Dialog -->
                    <ChapterAddUrlsDialog :open="showAddUrlsDialog" :loading="isLoading"
                        @update:open="showAddUrlsDialog = $event" @submit="handleAddFromUrls">
                        <Button variant="outline" class="gap-2">
                            <LinkIcon class="h-4 w-4" />
                            <span class="hidden sm:inline">Thêm từ URLs</span>
                        </Button>
                    </ChapterAddUrlsDialog>

                    <!-- Add Chapter Dialog -->
                    <ChapterAddDialog :open="showAddChapterDialog" :loading="isLoading"
                        @update:open="showAddChapterDialog = $event" @submit="handleAddChapter">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            <span class="hidden sm:inline">Thêm Chapter</span>
                        </Button>
                    </ChapterAddDialog>
                </div>
            </div>

            <!-- Search Bar -->
            <div v-if="chapters.length > 0" class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="searchQuery" type="text" placeholder="Tìm kiếm chapters..." class="pl-9 pr-9" />
                <button v-if="searchQuery" @click="searchQuery = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <Separator />

            <!-- Chapters List -->
            <div v-if="filteredChapters.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ArrowUpDown class="h-4 w-4 text-muted-foreground" />
                        <p class="text-sm text-muted-foreground">
                            Kéo thả để sắp xếp lại thứ tự chapters
                        </p>
                    </div>
                    <Badge variant="outline" class="text-xs">
                        {{ filteredChapters.length }} / {{ chapters.length }} chapters
                    </Badge>
                </div>

                <Card>
                    <CardContent class="p-0">
                        <draggable v-model="chapters" item-key="id" handle=".drag-handle" @start="onDragStart"
                            @end="onDragEnd" :disabled="isLoading" class="divide-y divide-border">
                            <template #item="{ element: chapter }">
                                <ChapterItem :chapter="chapter" :is-reordering="isReordering" @view="openViewDialog"
                                    @edit="openEditDialog" @delete="handleDeleteChapter" />
                            </template>
                        </draggable>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-16">
                    <div v-if="searchQuery" class="flex flex-col items-center gap-4 text-center">
                        <Search class="h-12 w-12 text-muted-foreground" />
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold">Không tìm thấy chapters</h3>
                            <p class="text-sm text-muted-foreground">
                                Không có chapter nào khớp với từ khóa "{{ searchQuery }}"
                            </p>
                        </div>
                        <Button variant="outline" @click="searchQuery = ''">
                            <X class="mr-2 h-4 w-4" />
                            Xóa bộ lọc
                        </Button>
                    </div>
                    <div v-else class="flex flex-col items-center gap-4 text-center">
                        <div class="rounded-full bg-muted p-6">
                            <FileArchive class="h-12 w-12 text-muted-foreground" />
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold">Chưa có chapter nào</h3>
                            <p class="text-sm text-muted-foreground">
                                Bắt đầu bằng cách thêm chapter đầu tiên của bạn
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button @click="showAddChapterDialog = true">
                                <Plus class="mr-2 h-4 w-4" />
                                Thêm Chapter
                            </Button>
                            <Button variant="outline" @click="showUploadZipDialog = true">
                                <Upload class="mr-2 h-4 w-4" />
                                Upload ZIP
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- View Chapter Dialog -->
            <ChapterViewDialog :open="showViewDialog" :chapter="selectedChapter" @update:open="showViewDialog = $event"
                @edit="openEditDialog" />

            <!-- Edit Dialog -->
            <ChapterEditDialog :open="showEditDialog" :chapter="selectedChapter" :servers="servers" :loading="isLoading"
                @update:open="showEditDialog = $event" @submit="handleUpdateChapterSubmit" />
        </div>
    </AppLayout>
</template>
