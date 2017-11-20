import '../../common.js'
import MyVuetable from '../../../../components/MyVuetable.vue'
import FilterBar from '../../../../components/MyFilterBar.vue'
import MyToastr from '../../../../components/MyToastr.vue'
import TableActions from './components/TableActions.vue'

Vue.component('table-actions', TableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    FilterBar,
    MyToastr,
  },
  data: {
    eventHub: new Vue(),
    activatedRow: {},
    createWhitelistData: {},

    whitelistApi: '/admin/api/game/whitelist',
    tableFields: [
      {
        name: 'playerid',
        title: '玩家id',
      },
      {
        name: 'nickname',
        title: '昵称',
      },
      {
        name: 'winrate',
        title: '胜率',
      },
      {
        name: '__component:table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
  },

  methods: {
    resolveResponse (res, toastr) {
      if (res.status === 422) {
        return toastr.message(JSON.stringify(res.data), 'error')
      }
      return res.data.error
        ? toastr.message(res.data.error, 'error')
        : toastr.message(res.data.message)
    },

    createWhitelist () {
      let _self = this
      let toastr = this.$refs.toastr

      axios({
        method: 'POST',
        url: this.whitelistApi,
        data: this.createWhitelistData,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          _self.resolveResponse(response, toastr)
          _self.createWhitelistData = {}
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    editWhitelist () {
      let _self = this
      let toastr = this.$refs.toastr

      axios({
        method: 'PUT',
        url: this.whitelistApi,
        data: this.activatedRow,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          _self.resolveResponse(response, toastr)
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    deleteWhitelist () {
      let _self = this
      let toastr = this.$refs.toastr
      let formData = {
        playerid: this.activatedRow.playerid,
      }

      axios({
        method: 'DELETE',
        url: this.whitelistApi,
        data: formData,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          _self.resolveResponse(response, toastr)
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
        .catch((error) => toastr.message(error, 'error'))
    },

    onMyVuetableError (data) {
      this.$refs.toastr.message(data.error, 'error')
    },
  },

  mounted () {
    let _self = this

    this.$root.eventHub.$on('editWhitelistEvent', (data) => _self.activatedRow = data)
    this.$root.eventHub.$on('deleteWhitelistEvent', (data) => _self.activatedRow = data)
    this.$root.eventHub.$on('MyVuetable:error', this.onMyVuetableError)
  },
})