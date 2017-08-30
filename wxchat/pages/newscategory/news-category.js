var CONFIG = require('../../utils/config.js')

Page({
  data:{
    
    
  },
  onLoad: function () {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_NEWS_CATEGORY,
      method: 'GET',
      data: {},
      header: {
        'Accept': 'application/json'
      },
      success: function(res) {
        console.log(res);

        if (res.statusCode == 200 && res.data.status == 'ok') {
          var data = res.data;
          var category = [];
          
          console.log(data);
          
          for (var i = 0; i < 5; i++) {
            category.push(data.posts[i]);
          }
          that.setData({category: category});
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
      url: '/pages/newsmore/news-more?id=' + event.currentTarget.dataset.type
    })
  }
})
