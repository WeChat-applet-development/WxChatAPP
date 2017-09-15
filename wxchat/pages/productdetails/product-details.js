var CONFIG = require('../../utils/config.js')
var WxParse = require('../../utils/wxParse/wxParse.js');

var app = getApp();
Page({
  data:{
    src1: "http://localhost:8008/mapi_v1/courses/1/lessons/1/media?token=r21godrkveskkcg0skwg80gco4o8w0o",
    src: "http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400&bizid=1023&hy=SH&fileparam=302c020101042530230204136ffd93020457e3c4ff02024ef202031e8d7f02030f42400204045a320a0201000400"
  },
  onLoad: function (options) {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_PRODUCT_DETAILS + options.id,
      method: 'GET',
      data: {},
      header: {
        'Accept': 'application/json'
      },
      success: function(res) {
        console.log(res);

        if (res.statusCode == 200 && res.data.status == 'ok') {        
          var data = res.data;

          that.setData({
            news: data.course,
            reviews: data.reviews,
            items: data.items,
            items2: data.items2,
          })
          WxParse.wxParse('about', 'html', data.course.about, that, 25)
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
      url: '/pages/productmedia/product-media?id=' + event.currentTarget.dataset.type + '&productid=' + this.news.id
    })
  }
})
