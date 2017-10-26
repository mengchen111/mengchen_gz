import '../../common.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import AiTableActions from './components/AiTableActions.vue'

Vue.component('ai-table-actions', AiTableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    MyToastr,
  },
  data: {
    eventHub: new Vue(),
    loading: true,
    activatedRow: {},   //被点击的行
    searchAiFormData: {
      db: 10014,        //游戏后端数据库id
      game_type: '',    //游戏类型
      status: '',       //状态
    },

    serverList: {},
    gameType: {},
    statusType: {},

    serverListApi: '/admin/api/game/server',
    gameTypeApi: '/admin/api/game/ai/type-map',
    editAiApi: '/admin/api/game/ai',

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
  },

  created: function () {
    let _self = this

    axios.get(this.serverListApi)
      .then((res) => _self.serverList = res.data)
    axios.get(this.gameTypeApi)
      .then((res) => {
        _self.gameType = res.data.game_type
        _self.statusType = res.data.status_type
      })

    this.loading = false
  },

  mounted: function () {
    //let _self = this

    this.$root.eventHub.$on('editAiEvent', (data) => this.activatedRow = data)
  },
})