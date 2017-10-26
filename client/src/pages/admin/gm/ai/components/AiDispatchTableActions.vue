<template>
    <div class="table-actions">
        <button class="btn btn-primary btn-flat" data-toggle="modal" data-target="#edit-ai-dispatch-modal"
                @click="editAiAction(rowData)">
            编辑
        </button>
    </div>
</template>

<script>
  export default {
    props: {
      rowData: {
        type: Object,
        required: true,
      },
      rowIndex: {
        type: Number,
      },
    },
    methods: {
      editAiAction (data) {
        this.$root.eventHub.$emit('editAiDispatchEvent', data)
      },
    },
  }
</script>