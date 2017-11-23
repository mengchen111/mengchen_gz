import '../index.js'
import MyVuetable from '../../../components/MyVuetable.vue'
import FilterBar from '../../../components/MyFilterBar.vue'
import TableActions from './components/TableActions.vue'
import DetailRow from './components/DetailRow.vue'

Vue.component('table-actions', TableActions)
Vue.component('detail-row', DetailRow)

new Vue({
  el: '#app',
  components: {
    FilterBar,
    MyVuetable,
  },
  data: {
    eventHub: new Vue(),
    agentType: {
      2: '总代理',
      3: '钻石代理',
      4: '金牌代理',
    },
    topUpData: {
      type: {
        1: '房卡',
        2: '金币',
      },
      typeId: 1,
      amount: null,
    },
    activatedRow: {
      group: '',
      parent: '',
      topUpType: 1,
      password: false,
    },                 //待编辑的行
    topUpApiPrefix: '/agent/api/top-up/child',
    editInfoApiPrefix: '/agent/api/subagent',
    deleteApiPrefix: '/agent/api/subagent',

    tableUrl: '/agent/api/subagent',
    tableFields: [
      {
        name: 'id',
        title: 'ID',
        sortField: 'id',
      },
      {
        name: 'name',
        title: '昵称',
      },
      {
        name: 'account',
        title: '登录账号',
        sortField: 'account',
      },
      {
        name: 'group.name',
        title: '代理级别',
        sortField: 'group_id',
      },
      {
        name: 'parent.account',
        title: '上级代理',
      },
      {
        name: 'inventorys',
        title: '房卡数量',
        callback: 'getCardsCount',
      },
      {
        name: 'inventorys',
        title: '金币数量',
        callback: 'getCoinsCount',
      },
      {
        name: '__component:table-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
    callbacks: {
      getCardsCount (inventorys) {
        if (0 === inventorys.length) {
          return null
        }
        for (let inventory of inventorys) {
          if (inventory.item.name === '房卡') {
            return inventory.stock
          }
        }
      },
      getCoinsCount (inventorys) {
        if (0 === inventorys.length) {
          return null
        }
        for (let inventory of inventorys) {
          if (inventory.item.name === '金币') {
            return inventory.stock
          }
        }
      },
    },
  },

  methods: {
    topUpChild () {
      let _self = this

      axios({
        method: 'POST',
        url: `${_self.topUpApiPrefix}/${_self.activatedRow.account}/${_self.topUpData.typeId}/${_self.topUpData.amount}`,
        validateStatus: function (status) {
          return status === 200 || status === 422 //定义哪些http状态返回码会被promise resolve
        },
      })
        .then(function (response) {
          if (response.status === 422) {
            alert(JSON.stringify(response.data))
          } else {
            response.data.error ? alert(response.data.error) : alert(response.data.message)
            _self.topUpData.amount = null
          }
        })
    },

    editChildInfo () {
      let _self = this

      let data = _self.activatedRow.password ? _self.activatedRow
        : _.omit(_self.activatedRow, 'password')

      axios({
        method: 'PUT',
        url: `${_self.editInfoApiPrefix}/${_self.activatedRow.id}`,
        data: data,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          if (response.status === 422) {
            _self.activatedRow.password = false
            return alert(JSON.stringify(response.data))
          }
          _self.activatedRow.password = false
          return alert(JSON.stringify(response.data.message))
        })
    },

    deleteAgent () {
      let _self = this

      axios({
        method: 'DELETE',
        url: `${_self.deleteApiPrefix}/${_self.activatedRow.id}`,
      })
        .then(function (response) {
          if (response.data.error) {
            return alert(response.data.error)
          }
          alert(response.data.message)

          //删除完成用户之后重新刷新表格数据，避免被删除用户继续留存在表格中
          _self.$root.eventHub.$emit('MyVuetable:refresh')
        })
    },
  },

  mounted: function () {
    let _self = this
    this.$root.eventHub.$on('topUpChildEvent', (data) => _self.activatedRow = data)
    this.$root.eventHub.$on('editInfoEvent', (data) => _self.activatedRow = data)
    this.$root.eventHub.$on('deleteAgentEvent', (data) => _self.activatedRow = data)
  },
})
