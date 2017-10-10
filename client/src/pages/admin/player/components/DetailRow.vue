<template>
    <div @click="onClick">
        <div class="">
            <label>上次登录ip:&nbsp;&nbsp;</label>
            <span>{{rowData.last_login_ip}}</span>
        </div>
        <div class="">
            <label>创建时间:&nbsp;&nbsp;</label>
            <span>{{rowData.create_time}}</span>
        </div>
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
      onClick () {
        //console.log('detail-row: on-click', event.target)
      },
    },
  }
</script>