<template>
    <div class="table-actions">
        <button class="btn btn-primary btn-flat" data-toggle="modal" data-target="#edit-server-modal"
                @click="editServerAction(rowData)">
            编辑
        </button>
        <button class="btn btn-danger btn-flat" data-toggle="modal" data-target="#delete-server-modal"
                @click="deleteServerAction(rowData)">
            删除
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
      editServerAction (data) {
        this.$root.eventHub.$emit('editServerEvent', data)
      },
      deleteServerAction (data) {
        this.$root.eventHub.$emit('deleteServerEvent', data)
      },
    },
  }
</script>