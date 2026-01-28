<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import axios from '@/axios';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import mangas from '@/routes/mangas';
import { getStorageUrl } from '@/utils/storage';
import {
    GripVertical,
    Plus,
    Upload,
    Link as LinkIcon,
    Trash2,
    Edit,
    Eye,
    Image as ImageIcon,
    FileArchive,
    Calendar,
    User,
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

// Form data
const uploadZipForm = ref({
    zipFile: null as File | null,
    fileName: '',
});

const addUrlsForm = ref({
    name: '',
    urls: '',
    description: '',
});

const addChapterForm = ref({
    name: '',
    description: '',
});

const editForm = ref({
    id: null as number | null,
    name: '',
    description: '',
    server_contents: [] as Array<{
        id?: number;
        manga_server_id?: number;
        urls: string;
        server_name?: string;
    }>,
});

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
    const reorderedChapters = chapters.value.map((chapter, index) => ({
        id: chapter.id,
        order: index,
    }));

    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.reorder(props.manga.id).url, {
            chapters: reorderedChapters,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            // Update local order
            chapters.value.forEach((chapter, index) => {
                chapter.order = index;
            });
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi sắp xếp lại chapters.');
        // Revert order on error
        chapters.value.sort((a, b) => a.order - b.order);
    } finally {
        isLoading.value = false;
    }
};

// Upload ZIP
const handleZipFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        uploadZipForm.value.zipFile = target.files[0];
        uploadZipForm.value.fileName = target.files[0].name;
    }
};

const handleUploadZip = async () => {
    if (!uploadZipForm.value.zipFile) {
        toast.error('Vui lòng chọn file ZIP.');
        return;
    }

    try {
        isLoading.value = true;
        const formData = new FormData();
        formData.append('manga_id', props.manga.id.toString());
        formData.append('zip_file', uploadZipForm.value.zipFile);

        const response = await axios.post(mangas.chapters.uploadZip(props.manga.id).url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value.push(response.data.chapter);
            chapters.value.sort((a, b) => a.order - b.order);
            showUploadZipDialog.value = false;
            uploadZipForm.value = {
                zipFile: null,
                fileName: '',
            };
            // Reset file input
            const fileInput = document.querySelector('#zip-file-input') as HTMLInputElement;
            if (fileInput) {
                fileInput.value = '';
            }
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi upload file ZIP.');
    } finally {
        isLoading.value = false;
    }
};

// Add from URLs
const handleAddFromUrls = async () => {
    if (!addUrlsForm.value.name.trim()) {
        toast.error('Vui lòng nhập tên chapter.');
        return;
    }

    if (!addUrlsForm.value.urls.trim()) {
        toast.error('Vui lòng nhập danh sách URLs.');
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.storeFromUrls(props.manga.id).url, {
            manga_id: props.manga.id,
            name: addUrlsForm.value.name,
            urls: addUrlsForm.value.urls,
            description: addUrlsForm.value.description,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value.push(response.data.chapter);
            chapters.value.sort((a, b) => a.order - b.order);
            showAddUrlsDialog.value = false;
            addUrlsForm.value = {
                name: '',
                urls: '',
                description: '',
            };
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo chapter từ URLs.');
    } finally {
        isLoading.value = false;
    }
};

// Add chapter
const handleAddChapter = async () => {
    if (!addChapterForm.value.name.trim()) {
        toast.error('Vui lòng nhập tên chapter.');
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.post(mangas.chapters.store(props.manga.id).url, {
            manga_id: props.manga.id,
            name: addChapterForm.value.name,
            description: addChapterForm.value.description,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value.push(response.data.chapter);
            chapters.value.sort((a, b) => a.order - b.order);
            showAddChapterDialog.value = false;
            addChapterForm.value = {
                name: '',
                description: '',
            };
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi tạo chapter.');
    } finally {
        isLoading.value = false;
    }
};

// Edit chapter
const openEditDialog = (chapter: MangaChapter) => {
    // Load server contents into form - đảm bảo load đúng server từ danh sách servers hiện có
    const serverContents = chapter.server_contents?.map((content) => {
        // Tìm server trong danh sách servers hiện có
        const server = props.servers.find((s) => s.id === content.server?.id);
        return {
            id: content.id,
            manga_server_id: server?.id || content.server?.id || undefined,
            urls: Array.isArray(content.urls) ? content.urls.join('\n') : '',
            server_name: server?.name || content.server?.name || 'Unknown Server',
        };
    }) || [];

    // If no server contents, add one empty entry với server đầu tiên
    if (serverContents.length === 0) {
        serverContents.push({
            manga_server_id: props.servers[0]?.id || undefined,
            urls: '',
            server_name: props.servers[0]?.name || 'Default Server',
        });
    }

    editForm.value = {
        id: chapter.id,
        name: chapter.name,
        description: chapter.description || '',
        server_contents: serverContents,
    };
    showEditDialog.value = true;
};

const handleUpdateChapter = async () => {
    if (!editForm.value.id) {
        return;
    }

    if (!editForm.value.name.trim()) {
        toast.error('Vui lòng nhập tên chapter.');
        return;
    }

    try {
        isLoading.value = true;
        
        // Prepare server contents data
        const serverContents = editForm.value.server_contents
            .filter((content) => content.urls.trim())
            .map((content) => ({
                id: content.id,
                manga_server_id: content.manga_server_id,
                urls: content.urls,
            }));

        const response = await axios.put(mangas.chapters.update(props.manga.id, editForm.value.id).url, {
            name: editForm.value.name,
            description: editForm.value.description,
            server_contents: serverContents,
        });

        if (response.data.success) {
            toast.success(response.data.message);
            const index = chapters.value.findIndex((c) => c.id === editForm.value.id);
            if (index !== -1) {
                chapters.value[index] = response.data.chapter;
            }
            showEditDialog.value = false;
            editForm.value = {
                id: null,
                name: '',
                description: '',
                server_contents: [],
            };
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
        const response = await axios.delete(mangas.chapters.destroy(props.manga.id, chapterId).url);

        if (response.data.success) {
            toast.success(response.data.message);
            chapters.value = chapters.value.filter((c) => c.id !== chapterId);
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa chapter.');
    } finally {
        isLoading.value = false;
    }
};

// Get image count
const getImageCount = (chapter: MangaChapter): number => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return 0;
    }
    return chapter.server_contents.reduce((total, content) => total + (content.urls?.length || 0), 0);
};

// Get first image URL for preview
const getFirstImageUrl = (chapter: MangaChapter): string | null => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return null;
    }
    const firstContent = chapter.server_contents[0];
    const url = firstContent.urls && firstContent.urls.length > 0 ? firstContent.urls[0] : null;
    return url ? getStorageUrl(url) : null;
};

// Format date
const formatDate = (dateString?: string): string => {
    if (!dateString) {
        return '';
    }
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Get all image URLs from chapter
const getAllImageUrls = (chapter: MangaChapter): string[] => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return [];
    }
    const allUrls: string[] = [];
    chapter.server_contents.forEach((content) => {
        if (content.urls && content.urls.length > 0) {
            content.urls.forEach((url) => {
                const storageUrl = getStorageUrl(url);
                if (storageUrl) {
                    allUrls.push(storageUrl);
                }
            });
        }
    });
    return allUrls;
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
                                    Tải lên file ZIP chứa các ảnh. Tên file ZIP sẽ được sử dụng làm tên chapter.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="flex flex-col gap-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="zip-file-input">File ZIP</Label>
                                    <div class="relative">
                                        <Input
                                            id="zip-file-input"
                                            type="file"
                                            accept=".zip"
                                            class="cursor-pointer"
                                            @change="handleZipFileChange"
                                        />
                                        <div
                                            v-if="uploadZipForm.fileName"
                                            class="mt-2 flex items-center gap-2 rounded-md border bg-muted/50 p-2 text-sm"
                                        >
                                            <FileArchive class="h-4 w-4 text-muted-foreground" />
                                            <span class="flex-1 truncate">{{ uploadZipForm.fileName }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Hỗ trợ các định dạng ảnh: JPG, PNG, GIF, WEBP. Tối đa 100MB.
                                    </p>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showUploadZipDialog = false" :disabled="isLoading">
                                    Hủy
                                </Button>
                                <Button @click="handleUploadZip" :disabled="isLoading || !uploadZipForm.zipFile">
                                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Đang upload...' : 'Upload' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Add from URLs Dialog -->
                    <Dialog v-model:open="showAddUrlsDialog">
                        <DialogTrigger as-child>
                            <Button variant="outline" class="gap-2">
                                <LinkIcon class="h-4 w-4" />
                                <span class="hidden sm:inline">Thêm từ URLs</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[650px]">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2">
                                    <LinkIcon class="h-5 w-5" />
                                    Thêm Chapter từ URLs
                                </DialogTitle>
                                <DialogDescription>
                                    Nhập tên chapter và danh sách URLs ảnh. Mỗi URL một dòng hoặc cách nhau bằng khoảng trắng.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="flex flex-col gap-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="urls-name">Tên Chapter *</Label>
                                    <Input
                                        id="urls-name"
                                        v-model="addUrlsForm.name"
                                        placeholder="Ví dụ: Chapter 1"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="urls-textarea">Danh sách URLs *</Label>
                                    <textarea
                                        id="urls-textarea"
                                        v-model="addUrlsForm.urls"
                                        class="min-h-[250px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Mỗi URL một dòng hoặc cách nhau bằng khoảng trắng
                                    </p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="urls-description">Mô tả (tùy chọn)</Label>
                                    <Input
                                        id="urls-description"
                                        v-model="addUrlsForm.description"
                                        placeholder="Mô tả chapter"
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showAddUrlsDialog = false" :disabled="isLoading">
                                    Hủy
                                </Button>
                                <Button @click="handleAddFromUrls" :disabled="isLoading">
                                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Đang thêm...' : 'Thêm' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Add Chapter Dialog -->
                    <Dialog v-model:open="showAddChapterDialog">
                        <DialogTrigger as-child>
                            <Button class="gap-2">
                                <Plus class="h-4 w-4" />
                                <span class="hidden sm:inline">Thêm Chapter</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[550px]">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2">
                                    <Plus class="h-5 w-5" />
                                    Thêm Chapter mới
                                </DialogTitle>
                                <DialogDescription>
                                    Tạo một chapter mới. Bạn có thể thêm nội dung (ảnh) sau.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="flex flex-col gap-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="chapter-name">Tên Chapter *</Label>
                                    <Input
                                        id="chapter-name"
                                        v-model="addChapterForm.name"
                                        placeholder="Ví dụ: Chapter 1"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="chapter-description">Mô tả (tùy chọn)</Label>
                                    <textarea
                                        id="chapter-description"
                                        v-model="addChapterForm.description"
                                        class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="Mô tả chapter"
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showAddChapterDialog = false" :disabled="isLoading">
                                    Hủy
                                </Button>
                                <Button @click="handleAddChapter" :disabled="isLoading">
                                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Đang thêm...' : 'Thêm' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Search Bar -->
            <div v-if="chapters.length > 0" class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Tìm kiếm chapters..."
                    class="pl-9 pr-9"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
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
                        <draggable
                            v-model="chapters"
                            item-key="id"
                            handle=".drag-handle"
                            @start="onDragStart"
                            @end="onDragEnd"
                            :disabled="isLoading"
                            class="divide-y divide-border"
                        >
                            <template #item="{ element: chapter }">
                                <div
                                    class="group relative flex items-center gap-4 p-4 transition-all hover:bg-muted/30"
                                    :class="{
                                        'bg-muted/50': isReordering,
                                    }"
                                >
                                    <!-- Drag Handle -->
                                    <div
                                        class="drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        <GripVertical class="h-5 w-5" />
                                    </div>

                                    <!-- Preview Image -->
                                    <div
                                        v-if="getFirstImageUrl(chapter)"
                                        class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border bg-muted"
                                    >
                                        <img
                                            :src="getFirstImageUrl(chapter)!"
                                            :alt="chapter.name"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg border bg-muted"
                                    >
                                        <ImageIcon class="h-6 w-6 text-muted-foreground" />
                                    </div>

                                    <!-- Chapter Info -->
                                    <div class="flex-1 min-w-0 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold truncate">{{ chapter.name }}</h3>
                                            <Badge variant="secondary" class="text-xs">
                                                #{{ chapter.order + 1 }}
                                            </Badge>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                            <div class="flex items-center gap-1">
                                                <ImageIcon class="h-3 w-3" />
                                                <span>{{ getImageCount(chapter) }} ảnh</span>
                                            </div>
                                            <div v-if="chapter.user" class="flex items-center gap-1">
                                                <User class="h-3 w-3" />
                                                <span>{{ chapter.user.name }}</span>
                                            </div>
                                            <div v-if="chapter.created_at" class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                <span>{{ formatDate(chapter.created_at) }}</span>
                                            </div>
                                        </div>
                                        <p v-if="chapter.description" class="line-clamp-1 text-sm text-muted-foreground">
                                            {{ chapter.description }}
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            @click="openViewDialog(chapter)"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            @click="openEditDialog(chapter)"
                                            title="Chỉnh sửa"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                            @click="handleDeleteChapter(chapter.id)"
                                            title="Xóa"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-16">
                    <div
                        v-if="searchQuery"
                        class="flex flex-col items-center gap-4 text-center"
                    >
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
                    <div
                        v-else
                        class="flex flex-col items-center gap-4 text-center"
                    >
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
            <Dialog v-model:open="showViewDialog">
                <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Eye class="h-5 w-5" />
                            Chi tiết Chapter
                        </DialogTitle>
                        <DialogDescription v-if="selectedChapter">
                            {{ selectedChapter.name }}
                        </DialogDescription>
                    </DialogHeader>
                    <div v-if="selectedChapter" class="space-y-6 py-4">
                        <!-- Chapter Info -->
                        <div class="space-y-4">
                            <div>
                                <Label class="text-xs text-muted-foreground">Tên Chapter</Label>
                                <p class="mt-1 font-semibold">{{ selectedChapter.name }}</p>
                            </div>
                            <div v-if="selectedChapter.description">
                                <Label class="text-xs text-muted-foreground">Mô tả</Label>
                                <p class="mt-1 text-sm">{{ selectedChapter.description }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-xs text-muted-foreground">Thứ tự</Label>
                                    <p class="mt-1 font-semibold">#{{ selectedChapter.order + 1 }}</p>
                                </div>
                                <div>
                                    <Label class="text-xs text-muted-foreground">Số ảnh</Label>
                                    <p class="mt-1 font-semibold">{{ getImageCount(selectedChapter) }} ảnh</p>
                                </div>
                                <div v-if="selectedChapter.user">
                                    <Label class="text-xs text-muted-foreground">Người tạo</Label>
                                    <p class="mt-1 text-sm">{{ selectedChapter.user.name }}</p>
                                </div>
                                <div v-if="selectedChapter.created_at">
                                    <Label class="text-xs text-muted-foreground">Ngày tạo</Label>
                                    <p class="mt-1 text-sm">{{ formatDate(selectedChapter.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Images Grid -->
                        <div v-if="getAllImageUrls(selectedChapter).length > 0">
                            <Label class="text-xs text-muted-foreground">Danh sách ảnh</Label>
                            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                <div
                                    v-for="(url, index) in getAllImageUrls(selectedChapter)"
                                    :key="index"
                                    class="group relative aspect-square overflow-hidden rounded-lg border bg-muted"
                                >
                                    <img
                                        :src="url"
                                        :alt="`Image ${index + 1}`"
                                        class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <Badge variant="secondary" class="text-xs">
                                            {{ index + 1 }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed p-8 text-center">
                            <ImageIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                            <p class="mt-2 text-sm text-muted-foreground">Chưa có ảnh nào trong chapter này</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showViewDialog = false">Đóng</Button>
                        <Button
                            v-if="selectedChapter"
                            @click="
                                openEditDialog(selectedChapter);
                                showViewDialog = false;
                            "
                        >
                            <Edit class="mr-2 h-4 w-4" />
                            Chỉnh sửa
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Dialog -->
            <Dialog v-model:open="showEditDialog">
                <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Edit class="h-5 w-5" />
                            Chỉnh sửa Chapter
                        </DialogTitle>
                        <DialogDescription>
                            Cập nhật thông tin chapter và nội dung server.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="flex flex-col gap-4 py-4">
                        <!-- Chapter Info -->
                        <div class="space-y-4">
                            <div class="flex flex-col gap-2">
                                <Label for="edit-name">Tên Chapter *</Label>
                                <Input
                                    id="edit-name"
                                    v-model="editForm.name"
                                    placeholder="Ví dụ: Chapter 1"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="edit-description">Mô tả (tùy chọn)</Label>
                                <textarea
                                    id="edit-description"
                                    v-model="editForm.description"
                                    class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                    placeholder="Mô tả chapter"
                                />
                            </div>
                        </div>

                        <!-- Server Contents Section -->
                        <Separator />
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Label class="text-base font-semibold">Nội dung Server</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        editForm.server_contents.push({
                                            manga_server_id: props.servers[0]?.id || undefined,
                                            urls: '',
                                            server_name: props.servers[0]?.name || 'New Server',
                                        })
                                    "
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Thêm Server
                                </Button>
                            </div>

                            <!-- Server Content Items -->
                            <div
                                v-for="(content, index) in editForm.server_contents"
                                :key="index"
                                class="space-y-3 rounded-lg border p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <Label class="font-medium">Server:</Label>
                                        <select
                                            v-model="content.manga_server_id"
                                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                            @change="
                                                const server = props.servers.find((s) => s.id === content.manga_server_id);
                                                if (server) {
                                                    content.server_name = server.name;
                                                } else {
                                                    content.server_name = 'Unknown Server';
                                                }
                                            "
                                        >
                                            <option :value="undefined">Chọn server</option>
                                            <option
                                                v-for="server in props.servers"
                                                :key="server.id"
                                                :value="server.id"
                                            >
                                                {{ server.name }}
                                            </option>
                                        </select>
                                        <span v-if="content.server_name" class="text-xs text-muted-foreground">
                                            ({{ content.server_name }})
                                        </span>
                                    </div>
                                    <Button
                                        v-if="editForm.server_contents.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="editForm.server_contents.splice(index, 1)"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`edit-urls-${index}`">
                                        URLs (mỗi URL một dòng hoặc cách nhau bằng khoảng trắng)
                                    </Label>
                                    <textarea
                                        :id="`edit-urls-${index}`"
                                        v-model="content.urls"
                                        class="min-h-[150px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        {{ content.urls.split(/[\s\n\r]+/).filter((url: string) => url.trim()).length }} URL(s) hợp lệ
                                    </p>
                                </div>
                            </div>

                            <div v-if="editForm.server_contents.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                                <p class="text-sm text-muted-foreground">Chưa có server content nào. Click "Thêm Server" để thêm.</p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showEditDialog = false" :disabled="isLoading">
                            Hủy
                        </Button>
                        <Button @click="handleUpdateChapter" :disabled="isLoading">
                            <Spinner v-if="isLoading" class="mr-2 h-4 w-4" />
                            {{ isLoading ? 'Đang cập nhật...' : 'Cập nhật' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
