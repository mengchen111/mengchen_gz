<template>
    <div class="table-actions">
        <!--<button class="btn btn-primary btn-flat" data-toggle="modal" data-target="#edit-ai-dispatch-modal"-->
                <!--@click="editAiDispatchAction(rowData)">-->
            <!--编辑-->
        <!--</button>-->
        <button class="btn btn-success btn-flat" @click="enableAiDispatchAction(rowData)">
            启用
        </button>
        <button class="btn btn-danger btn-flat" @click="disableAiDispatchAction(rowData)">
            停用
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
      editAiDispatchAction (data) {
        this.$root.eventHub.$emit('editAiDispatchEvent', data)
      },
      enableAiDispatchAction (data) {
        this.$root.eventHub.$emit('enableAiDispatchEvent', data)
      },
      disableAiDispatchAction (data) {
        this.$root.eventHub.$emit('disableAiDispatchEvent', data)
      },
    },
  }
</script>