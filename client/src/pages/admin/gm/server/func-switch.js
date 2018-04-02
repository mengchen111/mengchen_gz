import {myTools} from '../../index.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import TableActions from './components/TableActions.vue'
import vSelect from 'vue-select'

Vue.component('table-actions', TableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    MyToastr,
    vSelect,
  },
  data: {
    eventHub: new Vue(),
    activatedRow: {},
    httpClient: myTools.axiosInstance,
    msgResolver: myTools.msgResolver,

    verSwitch: {},
    funcStatus: {},
    deviceType: {},
    areaList: {},
    funcMarks: [],
    platformList: [],

    createServerData: {
      platform: 'yaowan-要玩',
      ver_switch: '平台版',
      area: 'default-默认',
      device_type: 'android-安卓',
      func_status: '关闭',
    },

    serverDataMapApi: '/admin/api/platform/server/func-switch/form-info',
    serverApiPrefix: '/admin/api/platform/server/func-switch',

    serverListApi: '/admin/api/platform/server/func-switch',
    tableFields: [
      {
        name: 'id',
        title: '功能控制ID',
      },
      {
        name: 'ver_switch',
        title: '版本信息',
        callback: 'transSwitch',
      },
      {
        name: 'area',
        title: '地区',
      },
      {
        name: 'device_type',
        title: '设备类型',
      },
      {
        name: 'platform',
        title: '渠道',
      },
      {
        name: 'client_version',
        title: '版本号',
      },
      {
        name: 'mark_name',
        title: '功能',
      },
      {
        name: 'func_status',
        title: '状态',
        callback: 'transStatus',
      },
      {
        name: '__component:table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
    tableCallbacks: {
      transStatus (value) {
        return this.$root.funcStatus[value]
      },
      transSwitch (value) {
        return this.$root.verSwitch[value]
      },
    },
  },

  methods: {
    onEditServer (data) {
      //拷贝对象，防止和 vuetable 字段相同出错
      this.activatedRow = _.cloneDeep(data)
      this.activatedRow.ver_switch = this.verSwitch[this.activatedRow.ver_switch]
      this.activatedRow.area = this.areaList[this.activatedRow.area]
      this.activatedRow.device_type = this.deviceType[this.activatedRow.device_type]
      this.activatedRow.func_status = this.funcStatus[this.activatedRow.func_status]
      this.activatedRow.func_mark = _.map(this.activatedRow.mark_name.split('|'))
      this.activatedRow.platform = this.platformList[this.activatedRow.platform]
    },

    editServer () {
      let _self = this
      let toastr = this.$refs.toastr
      let url = `${_self.serverApiPrefix}/${_self.activatedRow.id}`
      this.httpClient.put(url, _self.activatedRow)
        .then(function (response) {
          _self.msgResolver(response, toastr)
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
        .catch(function (err) {
          alert(err)
        })
    },

    createServer () {
      let _self = this
      let toastr = this.$refs.toastr
      this.httpClient.post(_self.serverApiPrefix, _self.createServerData)
        .then(function (response) {
          _self.msgResolver(response, toastr)
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
        .catch(function (err) {
          alert(err)
        })
    },

    deleteServer () {
      let _self = this
      let toastr = this.$refs.toastr
      let url = `${_self.serverApiPrefix}/${_self.activatedRow.id}`

      this.httpClient.delete(url)
        .then(function (response) {
          _self.msgResolver(response, toastr)
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
        .catch(function (err) {
          alert(err)
        })
    },
  },

  created: function () {
    let _self = this

    axios.get(this.serverDataMapApi)
      .then(function (res) {
        _self.funcMarks = res.data.func_marks
        _self.platformList = res.data.platform_list
        _self.areaList = res.data.area_list
        _self.verSwitch = res.data.ver_switch
        _self.funcStatus = res.data.func_status
        _self.deviceType = res.data.device_type
      })
  },

  mounted: function () {
    let _self = this
    this.$root.eventHub.$on('editServerEvent', this.onEditServer)
    this.$root.eventHub.$on('deleteServerEvent', (data) => _self.activatedRow = data)
  },
})