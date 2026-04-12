<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

const avatarSrc = computed(
    () => (props.user as any).avatar_url || props.user.avatar || '',
);

const showAvatar = computed(() => !!avatarSrc.value);

const roleColors: Record<string, string> = {
    admin:   'bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400',
    staff:   'bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400',
    faculty: 'bg-teal-100 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400',
    student: 'bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400',
};

const role = computed(() => (props.user as any).role as string | undefined);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="avatarSrc" :alt="user.name" />
        <AvatarFallback class="rounded-lg bg-gradient-to-br from-orange-400 to-teal-400 text-white font-bold text-xs">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
        <span
            v-else-if="role"
            :class="['inline-flex w-fit rounded px-1.5 py-0 text-[10px] font-semibold capitalize leading-4 mt-0.5', roleColors[role] ?? 'bg-slate-100 text-slate-600']"
        >{{ role }}</span>
    </div>
</template>
