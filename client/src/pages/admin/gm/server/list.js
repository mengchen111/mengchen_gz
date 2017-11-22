import '../../common.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import TableActions from './components/TableActions.vue'
import MyDatePicker from '../../../../components/MyDatePicker.vue'
import vSelect from 'vue-select'

Vue.component('table-actions', TableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    MyToastr,
    vSelect,
    MyDatePicker,
  },
  data: {
    eventHub: new Vue(),
    activatedRow: {},
    yesOrNoOptions: [
      '否',
      '是',
    ],
    areaList: {},
    serverStatusMap: [],
    serverTypeMap: [],

    serverDataMapApi: '/admin/api/platform/server/map',
    serverApiPrefix: '/admin/api/platform/server',

    serverListApi: '/admin/api/platform/server/list',
    tableFields: [
      {
        name: 'id',
        title: '游戏服ID',
      },
      {
        name: 'area',
        title: '地区',
      },
      {
        name: 'server_name',
        title: '名称',
      },
      {
        name: 'server_status',
        title: '状态',
      },
      {
        name: 'rate',
        title: '导量权重',
      },
      {
        name: 'is_update',
        title: '正在更新',
        callback: 'transValue',
      },
      {
        name: 'open_time',
        title: '开服时间',
      },
      {
        name: 'server_address',
        title: '场景服地址',
      },
      {
        name: 'server_type',
        title: '服务器类型',
      },
      {
        name: 'can_see',
        title: '是否展示',
        callback: 'transValue',
      },
      {
        name: 'is_cron',
        title: '是否执行统计脚本',
        callback: 'transValue',
      },
      {
        name: '__component:table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
    tableCallbacks: {
      transValue (value) {
        if (value === 1) {
          return '是'
        }
        return '否'
      },
    },
  },

  methods: {
    onEditServer (data) {
      this.activatedRow = data
      this.activatedRow.can_see_value = this.yesOrNoOptions[this.activatedRow.can_see]
      this.activatedRow.is_cron_value = this.yesOrNoOptions[this.activatedRow.is_cron]
    },

    editServer () {
      let _self = this
      let toastr = this.$refs.toastr

      this.activatedRow.area = _.findKey(this.areaList, function (value) {
        return value === _self.activatedRow.area
      })

      axios({
        method: 'PUT',
        url: `${_self.serverApiPrefix}/${_self.activatedRow.id}`,
        data: _self.activatedRow,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          if (response.status === 422) {
            return toastr.message(JSON.stringify(response.data), 'error')
          }
          return toastr.message(response.data.message)
        })
    },

  },

  created: function () {
    let _self = this

    axios.get(this.serverDataMapApi)
      .then(function (res) {
        _self.serverStatusMap = res.data.server_status_map
        _self.serverTypeMap = res.data.server_type_map
        _self.areaList = res.data.area_list
      })
  },

  mounted: function () {
    this.$root.eventHub.$on('editServerEvent', this.onEditServer)
  },
})