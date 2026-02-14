<template>
  <div class="py-1">
    <div
      class="flex items-center gap-2 py-2 px-2 rounded hover:bg-slate-50 group"
      :style="{ paddingLeft: `${12 + level * 20}px` }"
    >
      <span class="font-mono text-sm text-slate-500 w-16 shrink-0">{{ node.code }}</span>
      <span class="flex-1 text-slate-800">{{ node.name }}</span>
      <span class="capitalize text-slate-500 text-sm shrink-0">{{ node.type }}</span>
      <span class="opacity-0 group-hover:opacity-100 flex gap-1 shrink-0">
        <button type="button" @click="$emit('view-detail', node)" class="text-slate-500 hover:text-slate-700 text-xs">View</button>
        <button type="button" @click="$emit('edit', node)" class="text-slate-500 hover:text-slate-700 text-xs">Edit</button>
        <button
          v-if="!node.is_system"
          type="button"
          @click="$emit('delete', node)"
          class="text-red-500 hover:text-red-700 text-xs"
        >
          Delete
        </button>
      </span>
    </div>
    <AccountTreeRow
      v-for="child in node.children || []"
      :key="child.id"
      :node="child"
      :level="level + 1"
      @edit="$emit('edit', $event)"
      @delete="$emit('delete', $event)"
      @view-detail="$emit('view-detail', $event)"
    />
  </div>
</template>

<script setup>
defineProps({
  node: { type: Object, required: true },
  level: { type: Number, default: 0 },
});
defineEmits(['edit', 'delete', 'view-detail']);
</script>
