var CONFIG = require('../../utils/config.js')

Page({
  data:{
    
    
  },
  onLoad: function () {
    var that = this;
    wx.request({
      url: CONFIG.API_URL.GET_NEWS_MORE,
      method: 'GET',
      data: {},
      header: {
        'Accept': 'application/json'
      },
      success: function(res) {
        console.log(res);

        if (res.statusCode == 200 && res.data.status == 'ok' && res.data.total >= (res.data.limit+res.data.start)) {
          var data = res.data;
          var news = [];
          
          console.log(data);
          for (var i = 0; i < data.count; i++) {
            var excerpt_body = data.posts[i].body.replace(/<[^>].*?>/g, "");
            data.posts[i].excerpt_plain = excerpt_body.replace(/\[[^\]].*?\]/g, "");
            news.push(data.posts[i]);
          }
          that.setData({news: news});
        } else {
          var data = res.data;
          var news = [];
          
          console.log(data);
          for (var i = 0; i < (data.total-data.start); i++) {
            var excerpt_body = data.posts[i].body.replace(/<[^>].*?>/g, "");
            data.posts[i].excerpt_body = excerpt_body.replace(/\[[^\]].*?\]/g, "");
            news.push(data.posts[i]);
          }
          that.setData({news: news});
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
      url: '/pages/newsdetails/news-details?id=' + event.currentTarget.dataset.type
    })
  }
})
