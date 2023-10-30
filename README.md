[평가통계 API]

PHP Version : 7.3.1 <br />
Apache Version : 2.4.53


## 실행 방법

 - 개발 환경에서 직접 PHP 서버를 실행하기 위해서는 아래 작업을 진행한다

	설정파일 생성
	 : 현재 워크스페이스 위치에 따른 conf/ini 파일 재생성
	 : httpd.conf
	  - dev_env/http24/Apache24/conf/sample.conf 파일을 기반으로 디렉토리 정보 수정
	  - 포트 변경이 필요할 시 sample.conf 수정
	   : dev_env/http24/Apache24/conf/sample.conf 파일의 Listen 항목 원하는 포트로 지정 (ex. Listen 8080)
	 : php.ini
	  - dev_env/php73/sample.ini 파일을 기반으로 디렉토리 정보 수정
	 : dev_env/env_setting.bat 실행
	  - http.conf : PROJPATH 정보만 수정됨
	  - php.ini : extension_dir 정보만 수정됨
	  - git export 후 최초 1회 실행하면 되고, 포트 변경 혹은 경로 변경시 재실행 필요

 - 실행

	dev_env/startup.bat 실행
	 : 실행시 까만 콘솔창이 팝업됨
	 : 종료하려면 해당 콘솔창 선택 해 Ctrl-c 입력 (어떠한 방식이건 창을 닫으면 종료됨)
	주의 : 다른 프로세스가 지정한 포트를 사용하고 있으면 안됨
	실행 확인
	 - 브라우저에 localhost:지정한 포트(80 아닐 시) 입력하면 IndexOf / evalPhp/ 출력됨
	 - evalPhp 선택시 php 목록 출력
	 - 목록 중 phpinfo.php 선택시 설치내역 출력됨 / 정상 실행되는것임
	 

## eclipse PDT
eclipse --> Help(top menu) --> eclipse marketplace --> search: php --> install <br />
--> package Downloads (아래 설치 목록) <br />
- Dynamic Languages Toolki - Core Frameworks <br />
- Dynamic Languages Toolki - Core Lucene index Frameworks <br />
- WST Server UI <br />
<br />
[설치 이후] <br />
window(top menu) --> preferences --> PHP --> Installed PHPs --> add <br />
--> executable path [Browser] butten --> ../premium_eval-phpAPI/env/php73/php.exe <br />
--> Finish --> *.php 파일에 focus를 두고 Run as --> CLI or Web 으로 실행하기.
	

## 배포시 참고사항

 - redis
 
	(현)php 7.3과 호환이 되는 PHPRedis 5.3.5를 사용해야 함.
	 - (Token을 암호화/복호화하여 인증처리 방식에서 PHPRedis를 사용한 php session 방식으로 전환하여 필요.)
	
	Windows 10 (개발) / 아래 내용 작업됨
	 - ../php73 > ext > php_redis.dll 파일추가.
	 - ../php73 > php.ini:
	  : extension = php_redis.dll 코드추가.
	 - ../evalPhp/phpRedis.php
	  : ini_set(redis 관련 내용 추가) 코드추가 (Redis접속 URL 변경시 확인 필요).
	
	Linux (배포 - Ubuntu 22.04.2 LTS)
	 - php 7.3 패키지 설치에 대한 PHPRedis 패키지 설치
	  : apt-get install -y php7.3-redis
		
	참고 자료 : https://github.com/phpredis/phpredis/blob/develop/INSTALL.md)
	

 - mcrypt
 
	(현)loginAuto 시 사용되는 암호화 파라메터 처리시 필요함
	
	Windows 10 (개발) / 아래 내용 작업됨
	 - Download: https://windows.php.net/downloads/pecl/releases/mcrypt/1.0.4/php_mcrypt-1.0.4-7.3-ts-vc15-x64.zip
	 - ../php73 > ext > php_mcrypt.dll 파일추가.
	 - ../php73 > php.ini 
	  : error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED 코드추가.
	  : extension = php_mcrypt.dll 코드추가
	  
	Linux (배포)
	 - apt-get install -y php7.3-mcrypt
	 