URL:
  nomsg: 无显示信息
  referer: 登录后的跳转地址

API:
  调用地址: http://sso-server-domain/user/operation
  操作(operation): 返回 {status:操作状态, msg:错误信息, data:数据}
    createSubAccount:
      调用方式: POST
      参数:
        user_name: 用户名
        email: 邮箱地址
        password: 密码
        host: 当前网站域名
        time: 请求时间
        parent: 主帐号 ID
        token: md5(由 SSO 提供的与本站域名对应的 token + 请求时间)
        sendmail: 是否发送邮件(可选)
    updateSubAccountStatus:
      调用方式: POST
      参数:
        user_id: 子帐号 ID
        host: 当前网站域名
        time: 请求时间
        parent: 主帐号 ID
        status: 状态(activated, forbidden)
        token: md5(由 SSO 提供的与本站域名对应的 token + 请求时间)
    subAccountCounts:
      调用方式: GET
      参数:
        host: 当前网站域名
        time: 请求时间
        parent: 主帐号 ID
        status: 状态(activated, forbidden)
        account: 查询的帐号(用户名/邮箱地址)
        token: md5(由 SSO 提供的与本站域名对应的 token + 请求时间)
    subAccountList:
      调用方式: GET
      参数:
        host: 当前网站域名
        time: 请求时间
        parent: 主帐号 ID
        status: 状态(activated, forbidden)
        account: 查询的帐号(用户名/邮箱地址)
        start: 记录起始编号, 默认为0
        offset: 请求的数据数量，默认为20
        token: md5(由 SSO 提供的与本站域名对应的 token + 请求时间)
    import:
      调用方式: POST
      参数:
        host: 当前网站域名
        time: 请求时间
        token: md5(由 SSO 提供的与本站域名对应的 token + 请求时间)
        users: [
          {
            user_name: a,
            email: b,
            password: c,
            subaccounts: [
              {
                user_name: aa,
                email: bb,
                password: cc
              }
            ]
          }
        ]
