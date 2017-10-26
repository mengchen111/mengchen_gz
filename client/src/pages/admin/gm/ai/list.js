import '../../common.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import MyDatePicker from '../../../../components/MyDatePicker.vue'
import AiTableActions from './components/AiTableActions.vue'
import AiDispatchTableActions from './components/AiDispatchTableActions.vue'

Vue.component('ai-table-actions', AiTableActions)
Vue.component('ai-dispatch-table-actions', AiDispatchTableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    MyToastr,
    MyDatePicker,
  },
  data: {
    eventHub: new Vue(),
    loading: true,
    activatedRow: {},   //待编辑的行数据
    searchAiFormData: {
      db: 10014,        //游戏后端数据库id
      game_type: '',    //游戏类型
      status: '',       //状态
    },

    serverList: {},
    gameType: {},
    statusType: {},
    roomType: {},

    serverListApi: '/admin/api/game/server',
    typeApi: '/admin/api/game/ai/type-map',
    editAiApi: '/admin/api/game/ai',
    editAiDispatchApi: '/admin/api/game/ai-dispatch',
    switchAiDispatchApi: '/admin/api/game/ai-dispatch/switch', //启用停用

    aiTableUrl: '/admin/api/game/ai/list',
    aiTableFields: [
      {
        name: 'rid',
        title: 'id',
      },
      {
        name: 'nick',
        title: '昵称',
      },
      {
        name: 'diamond',
        title: '钻石',
      },
      {
        name: 'crystal',
        title: '兑换券',
      },
      {
        name: 'exp',
        title: '经验',
      },
      {
        name: 'duration',
        title: '调用天数',
      },
      {
        name: 'game_type',
        title: '游戏类型',
      },
      {
        name: 'room_type',
        title: '房间类型',
      },
      {
        name: 'status',
        title: '状态',
      },
      {
        name: 'create_time',
        title: '创建时间',
      },
      {
        name: '__component:ai-table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],

    aiDispatchTableUrl: '/admin/api/game/ai/dispatch/list',
    aiDispatchTableFields: [
      {
        name: 'id',
        title: 'id',
      },
      {
        name: 'start_vs_end_date',
        title: '开始/结束日期',
      },
      {
        name: 'start_vs_end_time',
        title: '开始/结束时间',
      },
      {
        name: 'theme',
        title: '主题',
      },
      {
        name: 'room_type',
        title: '房间',
      },
      {
        name: 'game_type',
        title: '游戏类型',
      },
      {
        name: 'golds',
        title: '金币数',
      },
      {
        name: 'num',
        title: 'ai数量',
      },
      {
        name: 'create_time',
        title: '创建时间',
      },
      {
        name: 'creator',
        title: '创建人',
      },
      {
        name: 'is_open',
        title: '状态',
      },
      {
        name: '__component:ai-dispatch-table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
  },

  methods: {
    getAiTableUrl () {
      return '/admin/api/game/ai/list'
    },

    getAiDispatchTableUrl () {
      return '/admin/api/game/ai/dispatch/list'
    },

    searchAiList () {
      //刷新表格，通过方法拿地址前缀，不然下一次提交查询，参数会append上去，造成错误
      this.aiTableUrl = this.getAiTableUrl() + `?db=${this.searchAiFormData.db}`
        + `&game_type=${this.searchAiFormData.game_type}`
        + `&status=${this.searchAiFormData.status}`
    },

    editAi () {
      let _self = this
      let toastr = this.$refs.toastr

      this.loading = true
      axios.put(this.editAiApi, this.activatedRow)
        .then((response) => {
          _self.loading = false
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message(response.data.message)
        })
    },

    editAiDispatch () {
      let _self = this
      let toastr = this.$refs.toastr

      //转换is_all_day的值
      this.activatedRow.is_all_day = this.activatedRow.is_all_day ? 1 : 0

      this.loading = true
      axios.put(this.editAiDispatchApi, this.activatedRow)
        .then((response) => {
          _self.loading = false
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message(response.data.message)
        })
    },

    aiListButtonAction () {
      this.aiTableUrl = this.getAiTableUrl()  //刷新表格
      this.searchAiFormData = {
        db: 10014,
        game_type: '',
        status: '',
      }
    },

    aiDispatchListButtonAction () {
      this.aiDispatchTableUrl = this.getAiDispatchTableUrl()  //刷新表格
      this.searchAiFormData = {
        db: 10014,
        game_type: '',
        status: '',
      }
    },

    searchAiDispatchList () {
      //刷新表格，通过方法拿地址前缀，不然下一次提交查询，参数会append上去，造成错误
      this.aiDispatchTableUrl = this.getAiDispatchTableUrl() + `?db=${this.searchAiFormData.db}`
        + `&game_type=${this.searchAiFormData.game_type}`
        + `&is_open=${this.searchAiFormData.status}`
    },

    enableAiDispatch (data) {
      let toastr = this.$refs.toastr

      axios.put(`${this.switchAiDispatchApi}/${data.id}/1`, {
        ids: data.ids
      })
        .then(function (response) {
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message('启用成功')
        })
    },

    disableAiDispatch (data) {
      let toastr = this.$refs.toastr

      axios.put(`${this.switchAiDispatchApi}/${data.id}/0`, {
        ids: data.ids
      })
        .then(function (response) {
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message('停用成功')
        })
    },
  },

  created: function () {
    let _self = this

    axios.get(this.serverListApi)
      .then((res) => _self.serverList = res.data)
    axios.get(this.typeApi)
      .then((res) => {
        _self.gameType = res.data.game_type
        _self.statusType = res.data.status_type
        _self.roomType = res.data.room_type
      })

    this.loading = false
  },

  mounted: function () {
    this.$root.eventHub.$on('editAiEvent', (data) => this.activatedRow = data)
    this.$root.eventHub.$on('editAiDispatchEvent', (data) => this.activatedRow = data)

    this.$root.eventHub.$on('enableAiDispatchEvent', (data) => this.enableAiDispatch(data))
    this.$root.eventHub.$on('disableAiDispatchEvent', (data) => this.disableAiDispatch(data))
  },
})