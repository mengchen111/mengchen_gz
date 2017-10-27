import '../../common.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import MyDatePicker from '../../../../components/MyDatePicker.vue'
import AiTableActions from './components/AiTableActions.vue'
import AiDispatchTableActions from './components/AiDispatchTableActions.vue'
import tableFields from './tableFields.js'

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
    massEditAiFormData: {},
    addAiDispatchFormData: {},

    serverList: {},
    gameType: {},
    statusType: {},
    roomType: {},

    serverListApi: '/admin/api/game/server',
    typeApi: '/admin/api/game/ai/type-map',
    editAiApi: '/admin/api/game/ai',
    massEditAiApi: '/admin/api/game/ai/mass',
    editAiDispatchApi: '/admin/api/game/ai-dispatch',
    addAiDispatchApi: '/admin/api/game/ai-dispatch',
    switchAiDispatchApi: '/admin/api/game/ai-dispatch/switch', //启用停用

    aiSelectedTo: [],   //被选中的行rid
    aiTableUrl: '/admin/api/game/ai/list',
    aiTableTrackBy: 'rid',
    aiTableFields: tableFields.aiTableFields,

    aiDispatchTableUrl: '/admin/api/game/ai/dispatch/list',
    aiDispatchTableFields: tableFields.aiDispatchTableFields,
  },

  computed: {
    selectedAiIds: function () {
      return this.aiSelectedTo.join(',')
    },
    selectedAiAmount: function () {
      return this.aiSelectedTo.length
    },
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

      this.aiSelectedTo = []  //清空选择框
      this.$root.eventHub.$emit('vuetableFlushSelectedTo')
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
        .catch((error) => toastr.message(error, 'error'))
    },

    massEditAi () {
      let _self = this
      let toastr = this.$refs.toastr

      if (this.aiSelectedTo.length === 0) {
        return toastr.message('未选中ai', 'error')
      }

      this.loading = true
      Object.assign(this.massEditAiFormData, {
        id: this.selectedAiIds,
      })
      axios.put(this.massEditAiApi, this.massEditAiFormData)
        .then((response) => {
          _self.loading = false
          _self.massEditAiFormData = {}

          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message(response.data.message)
        })
        .catch((error) => toastr.message(error, 'error'))
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
        .catch((error) => toastr.message(error, 'error'))
    },

    addAiDispatch () {
      let _self = this
      let toastr = this.$refs.toastr

      if (this.aiSelectedTo.length === 0) {
        return toastr.message('未选中ai', 'error')
      }

      //构建后端接口需要的参数
      this.addAiDispatchFormData.isAllDay = this.addAiDispatchFormData.isAllDay ? 1 : 0
      Object.assign(this.addAiDispatchFormData, {
        serverId: this.searchAiFormData.db,
      })

      this.loading = true
      Object.assign(this.addAiDispatchFormData, {
        id: this.selectedAiIds,
      })
      axios.post(this.addAiDispatchApi, this.addAiDispatchFormData)
        .then((response) => {
          _self.loading = false
          _self.addAiDispatchFormData = {}

          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message(response.data.message)
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    aiListButtonAction () {
      this.aiTableUrl = this.getAiTableUrl()  //刷新表格
      this.searchAiFormData = {
        db: 10014,
        game_type: '',
        status: '',
      }
      this.aiSelectedTo = []  //清空选择框
      this.$root.eventHub.$emit('vuetableFlushSelectedTo')
    },

    aiDispatchListButtonAction () {
      this.aiDispatchTableUrl = this.getAiDispatchTableUrl()  //刷新表格
      this.searchAiFormData = {
        db: 10014,
        game_type: '',
        status: '',
      }
      this.aiSelectedTo = []  //清空选择框
      this.$root.eventHub.$emit('vuetableFlushSelectedTo')
    },

    searchAiDispatchList () {
      //刷新表格，通过方法拿地址前缀，不然下一次提交查询，参数会append上去，造成错误
      this.aiDispatchTableUrl = this.getAiDispatchTableUrl() + `?db=${this.searchAiFormData.db}`
        + `&game_type=${this.searchAiFormData.game_type}`
        + `&is_open=${this.searchAiFormData.status}`

      this.aiSelectedTo = []  //清空选择框
      this.$root.eventHub.$emit('vuetableFlushSelectedTo')
    },

    enableAiDispatch (data) {
      let toastr = this.$refs.toastr

      axios.put(`${this.switchAiDispatchApi}/${data.id}/1`, {
        ids: data.ids,
      })
        .then(function (response) {
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message('启用成功')
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    disableAiDispatch (data) {
      let toastr = this.$refs.toastr

      axios.put(`${this.switchAiDispatchApi}/${data.id}/0`, {
        ids: data.ids,
      })
        .then(function (response) {
          return response.data.error
            ? toastr.message(response.data.error, 'error')
            : toastr.message('停用成功')
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    onVuetableCheckboxToggled (isChecked, data) {
      if (isChecked === true) {
        this.aiSelectedTo.push(data[this.aiTableTrackBy])
      } else {
        _.pull(this.aiSelectedTo, data[this.aiTableTrackBy])
      }
    },
    onVuetableCheckboxToggledAll (isChecked, data) {
      if (isChecked === true) {
        this.aiSelectedTo = data
      } else {
        this.aiSelectedTo = []
        this.$root.eventHub.$emit('vuetableFlushSelectedTo')
      }
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
    this.$root.eventHub.$on('vuetableCheckboxToggled', (isChecked, data) => this.onVuetableCheckboxToggled(isChecked, data))
    this.$root.eventHub.$on('vuetableCheckboxToggledAll', (isChecked, data) => this.onVuetableCheckboxToggledAll(isChecked, data))
  },
})