import { myTools } from '../index.js'
import MyVuetable from '../../../components/MyVuetable.vue'
import MyToastr from '../../../components/MyToastr.vue'
import ItemTableActions from './components/ItemTableActions.vue'

Vue.component('table-actions', ItemTableActions)

new Vue({
  el: '#app',
  components: {
    MyVuetable,
    MyToastr,
  },
  data: {
    eventHub: new Vue(),
    activatedRow: null,

    apiPrefix: '/admin/api/order/item',
    tableUrl: '/admin/api/order/item',
    tableFields: [
      {
        name: 'id',
        title: 'id',
      },
      {
        name: 'name',
        title: '道具名称',
      },
      {
        name: 'price',
        title: '道具价格',
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
    editPrice () {
      let toastr = this.$refs.toastr

      myTools.axiosInstance.put(this.apiPrefix + '/' + this.activatedRow.id, this.activatedRow)
        .then(function (res) {
          myTools.msgResolver(res, toastr)
        })
        .catch(err => alert(err))
    },
  },

  mounted () {
    let _self = this
    this.$root.eventHub.$on('editPriceEvent', data => _self.activatedRow = data)
  },
})