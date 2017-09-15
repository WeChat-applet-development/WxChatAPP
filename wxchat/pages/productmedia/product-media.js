var CONFIG = require('../../utils/config.js')
var WxParse = require('../../utils/wxParse/wxParse.js');

var app = getApp();
Page({
  data:{
  },
  onLoad: function (options) {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_PRODUCT_MEDIA + options.productid + '/lessons/' + options.id + '?token=r21godrkveskkcg0skwg80gco4o8w0o',
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
