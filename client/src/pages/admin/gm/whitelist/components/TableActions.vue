<template>
    <div class="row">
        <button class="btn btn-small btn-primary btn-flat" data-toggle="modal" data-target="#edit-whitelist-modal"
                @click="editWhitelistAction(rowData)">
            编辑
        </button>
        <button class="btn btn-small btn-danger btn-flat" data-toggle="modal" data-target="#delete-whitelist-modal"
                @click="deleteWhitelistAction(rowData)">
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
      editWhitelistAction (data) {
        this.$root.eventHub.$emit('editWhitelistEvent', data)
      },
      deleteWhitelistAction (data) {
        this.$root.eventHub.$emit('deleteWhitelistEvent', data)
      },
    },
  }
</script>