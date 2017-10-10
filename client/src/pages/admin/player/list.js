import Vue from 'vue'
import axios from 'axios'
import MyVuetable from '../../../components/MyVuetable.vue'
import FilterBar from '../../../components/FilterBar.vue'
import DetailRow from './components/DetailRow.vue'
import TableActions from './components/TableActions.vue'

Vue.component('custom-actions', TableActions)
Vue.component('detail-row', DetailRow)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    FilterBar,
  },
  data: {
    eventHub: new Vue(),
    activatedRow: {},
    topUpData: {
      type: {
        1: '房卡',
        2: '金币',
      },
      typeId: 1,
      amount: null,
    },
    onlineState: ['离线', '在线'],

    tableUrl: '/admin/api/game/player',
    tableTrackBy: 'rid',
    tableFields: [
      {
        name: 'rid',
        title: '玩家ID',
        sortField: 'rid',
      },
      {
        name: 'nick',
        title: '玩家昵称',
      },
      {
        name: 'card.count',
        title: '房卡数量',
      },
      {
        name: 'gold',
        title: '金币数量',
        sortField: 'gold',
      },
      {
        name: 'online',
        title: '在线状态',
        sortField: 'online',
        callback: 'getOnlineState',
      },
      {
        name: 'last_login_time',
        title: '最后登录时间',
      },
      {
        name: 'last_offline_time',
        title: '最后离线时间',
      },
      {
        name: '__component:custom-actions',
        title: '操作',
        titleClass: 'text-center',
        dataClass: 'text-center',
      },
    ],
    tableSortOrder: [      //默认的排序
      {
        field: 'rid',
        sortField: 'rid',
        direction: 'desc',
      },
    ],
  },

  methods: {
    getOnlineState(state) {
      return this.onlineState[state]
    },

    topUpPlayer() {
      let _self = this
      let apiUrl = `/admin/api/top-up/player/${_self.activatedRow.rid}/${_self.topUpData.typeId}/${_self.topUpData.amount}`

      axios({
        method: 'POST',
        url: apiUrl,
        validateStatus: function (status) {
          return status === 200 || status === 422
        },
      })
        .then(function (response) {
          if (response.status === 422) {
            alert(JSON.stringify(response.data))
          } else {
            response.data.error ? alert(response.data.error) : alert(response.data.message)
            _self.topUpData.amount = null
            _self.$root.eventHub.$emit('vuetableRefresh')  //重新刷新表格
          }
        })
        .catch(function (err) {
          alert(err)
        })
    },
  },

  mounted: function () {
    let _self = this

    this.$root.eventHub.$on('topUpPlayerEvent', function (data) {
      _self.activatedRow = data
    })
    this.$root.eventHub.$on('vuetableDataError', (data) => alert(data.error))
  },
})
