var CONFIG = require('../../utils/config.js')
var WxParse = require('../../utils/wxParse/wxParse.js');

var app = getApp();
Page({
  data:{
  },
  onLoad: function (options) {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_PRODUCT_MEDIA + options.productid + '/lessons/' + options.id + '?token=jna5j842vqgoo04o0wgowoc88w8kgsk',
      method: 'GET',
      data: {},
      header: {
        'Accept': 'application/json'
      },
      success: function(res) {
        console.log(res);
        
          var data = res.data;

          that.setData({
            mediaUri: data.mediaUri,
            title: data.title,
            summary: data.summary,
          })
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
  }
})
