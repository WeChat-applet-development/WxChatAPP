var CONFIG = require('../../utils/config.js')
var WxParse = require('../../utils/wxParse/wxParse.js');

Page({
  data: {
    latitude: 23.099994,
    longitude: 113.324520,
    markers: [{
      latitude: 23.099994,
      longitude: 113.324520,
      name: '商家产品发布平台'
    }],
    covers: [{
      latitude: 23.099994,
      longitude: 113.344520,
      iconPath: '/images/green_tri.png', // 目前有 bug，正确的写法应该是 /image/green_tri.png ，等我们下个版本修复吧T_T 
    }, {
      latitude: 23.099994,
      longitude: 113.304520,
      iconPath: '/images/green_tri.png',
      rotate: 180
    }]
  },
  onLoad: function () {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_PAGE,
      method: 'GET',
      data: {},
      header: {
        'Accept': 'application/json'
      },
      success: function(res) {
        console.log(res);

        if (res.statusCode == 200 && res.data.status == 'ok') {
          var data = res.data;
          
          that.setData({site: data.site});
          WxParse.wxParse('content', 'html', data.site.name, that, 25)
        } else {
          
        }
      }
    })
  },
  onReady:function(){
    // 页面渲染完成
  },
  onShow:function(){
    // 页面显示
  },
  onHide:function(){
    // 页面隐藏
  },
  onUnload:function(){
    // 页面关闭
  },
  go: function(event) {
    wx.navigateTo({
      url: '/pages/news/news-details?id=' + event.currentTarget.dataset.type
    })
  }
})
