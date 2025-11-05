<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>官方网站</title>
    <style>
        body, html { margin: 0; padding: 0; height: 100vh; overflow: hidden; background-color: #f5f5f5; font-family: Arial, sans-serif; }
        iframe { width: 100%; height: 100%; border: none; display: none; }
        .error-container, .contact-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background-color: rgba(245,245,245,0.8); }
        .error-box, .contact-box { padding: 40px 60px; border-radius: 12px; box-shadow: 0 4px 25px rgba(0,0,0,0.2); text-align: center; transition: background-image 0.5s ease; min-width: 280px; }
        .access-denied { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .whitelist-error { font-size: 16px; margin-bottom: 20px; line-height: 1.5; }
  
        .contact-info { font-size: 16px; font-weight: bold; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>
    <iframe id="embeddedPage" src=""></iframe>
    <div id="errorContainer" class="error-container" style="display: none;">
        <div class="error-box" id="errorBox">
            <div class="access-denied">访问受限</div>
            <div class="whitelist-error">域名未授权</div>
           
            <a class="contact-info" href="https://t.me/xyfhnb" target="_blank" rel="noopener noreferrer">点击联系相遇TG@xyfhnb</a>
        </div>
    </div>
    <div id="contactContainer" class="contact-container" style="display: none;">
        <div class="contact-box" id="contactBox">
           
            <a class="contact-info" href="https://t.me/xyfhnb" target="_blank" rel="noopener noreferrer">点击联系相遇TG@xyfhnb</a>
        </div>
    </div>

    <script>
    
        const CONFIG = {
            WHITELIST_API: 'https://m.myikj1.top/xyapi.php', 
            FALLBACK_WHITELIST: [],
            WHITELIST_CACHE_TIME: 300000,
            TIMEOUT: 8000
        };

        let currentWhitelist = [];
        let lastUpdateTime = 0;

    
        function showState(state) {
            document.getElementById('errorContainer').style.display = 'none';
            document.getElementById('contactContainer').style.display = 'none';
            document.getElementById('embeddedPage').style.display = 'none';
            switch(state) {
                case 'error':
                    document.getElementById('errorContainer').style.display = 'flex';
                    document.title = '访问受限';
                    setRandomGradient('errorBox');
                    break;
                case 'contact':
                    document.getElementById('contactContainer').style.display = 'flex';
                    document.title = '相遇TG@xyfhnb';
                    setRandomGradient('contactBox');
                    break;
                case 'success':
                    document.getElementById('embeddedPage').style.display = 'block';
                    document.title = '在线客服';
                    break;
            }
        }

    
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) color += letters[Math.floor(Math.random() * 16)];
            return color;
        }

        function getRandomGradientDirection() {
            const directions = [0, 45, 90, 135, 180, 225, 270, 315];
            return directions[Math.floor(Math.random() * directions.length)];
        }

        function getColorBrightness(color) {
            const r = parseInt(color.slice(1, 3), 16);
            const g = parseInt(color.slice(3, 5), 16);
            const b = parseInt(color.slice(5, 7), 16);
            return (r * 299 + g * 587 + b * 114) / 1000;
        }

        function setRandomGradient(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const color1 = getRandomColor();
            const color2 = getRandomColor();
            const direction = getRandomGradientDirection();
            container.style.backgroundImage = `linear-gradient(${direction}deg, ${color1}, ${color2})`;
            const avgBrightness = (getColorBrightness(color1) + getColorBrightness(color2)) / 2;
            const textColor = avgBrightness < 128 ? '#ffffff' : '#000000';
          
            container.querySelectorAll('div, .contact-info').forEach(el => el.style.color = textColor);
        }

  
        async function fetchWhitelist() {
            if (Date.now() - lastUpdateTime < CONFIG.WHITELIST_CACHE_TIME) {
                console.log('→ 使用缓存白名单：', currentWhitelist);
                return;
            }

            try {
                console.log('→ 发起远程API请求：', CONFIG.WHITELIST_API);
                const response = await Promise.race([
                    fetch(CONFIG.WHITELIST_API + '?t=' + Date.now(), {
                        method: 'GET',
                        mode: 'cors',
                        headers: { 'Accept': 'application/json' }
                    }),
                    new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('API请求超时')), CONFIG.TIMEOUT)
                    )
                ]);

                if (!response.ok) throw new Error(`API状态码错误：${response.status} ${response.statusText}`);
                const data = await response.json();
                console.log('→ 远程API返回数据：', data);

                if (data.status !== 'success' || !Array.isArray(data.domains)) {
                    throw new Error('远程API返回格式无效，需包含{status:"success",domains:[]}');
                }

                const formattedDomains = data.domains
                    .map(d => d?.toString().trim() || '')
                    .filter(d => d !== '')
                    .map(d => d.replace(/^https?:\/\//, '').replace('www.', '').split('/')[0].toLowerCase())
                    .filter(d => d.includes('.'));

                if (formattedDomains.length === 0) {
                    throw new Error('远程白名单为空，无有效域名');
                }

                currentWhitelist = formattedDomains;
                lastUpdateTime = Date.now();
                console.log('→ 远程白名单更新成功：', currentWhitelist);

            } catch (error) {
                console.error('→ 远程白名单获取失败：', error.message);
                throw error;
            }
        }

   
        function getQueryParam(param) {
            return new URLSearchParams(window.location.search).get(param);
        }

   
        function decodeBase64Url(encodedUrl) {
            try {
                return decodeURIComponent(atob(encodedUrl.replace(/-/g, '+').replace(/_/g, '/')));
            } catch (e) {
                console.error('→ Base64解码失败：', e.message);
                return '';
            }
        }

       
        function extractDomain(url) {
            try {
                const domain = new URL(url).hostname;
                const pureDomain = domain.replace('www.', '').toLowerCase();
                console.log('→ 提取域名：', url, '→', pureDomain);
                return pureDomain;
            } catch (e) {
                console.error('→ 提取域名失败：', e.message);
                return null;
            }
        }

      
        function isDomainAllowed(url) {
            const domain = extractDomain(url);
            if (!domain) return false;
            const isAllowed = currentWhitelist.some(whitelistDomain => {
                const match = domain === whitelistDomain || domain.endsWith('.' + whitelistDomain);
                console.log('→ 匹配域名：', domain, 'vs', whitelistDomain, '→', match ? '通过' : '拒绝');
                return match;
            });
            return isAllowed;
        }


        async function main() {
            const encodedUrl = getQueryParam('xy');
            if (!encodedUrl) {
                showState('contact');
                return;
            }

            try {
                await fetchWhitelist();
                const decodedUrl = decodeBase64Url(encodedUrl);
                if (!decodedUrl) throw new Error('目标URL解码失败');
                console.log('→ 解码后目标URL：', decodedUrl);
                if (!isDomainAllowed(decodedUrl)) throw new Error('目标域名不在远程白名单中');
                showState('success');
                document.getElementById('embeddedPage').src = decodedUrl;
            } catch (error) {
                console.error('→ 主流程错误：', error.message);
                showState('error');
            }
        }

        main();
    </script>
</body>
</html>