# MicroBoard v1.0.0 - 기능 업데이트

## 🎉 새로운 기능 (Latest Updates)

### 🔐 고급 권한 시스템 (Advanced Permission System)
게시판별로 세밀한 접근 제어가 가능합니다.

- **10단계 레벨 시스템**: 레벨 0(비회원)~10(관리자)
- **게시판별 독립 권한**: 목록/읽기/쓰기 권한 개별 설정
- **비회원 접근 제어**: 레벨 0 설정으로 비회원도 목록 조회 가능
- **자동 권한 체크**: 권한 부족 시 친절한 안내 메시지

**설정 방법:**
1. 관리자 페이지 → 게시판 관리
2. 게시판 편집 → 권한 설정 섹션
3. 목록/읽기/쓰기 권한 레벨 선택

### 🔍 통합검색 시스템 (Integrated Search)
모든 게시판을 한 번에 검색할 수 있습니다.

- **전체 게시판 검색**: 모든 게시판 동시 검색
- **검색어 하이라이트**: 검색 결과에서 검색어 강조 표시
- **게시판별 필터**: 특정 게시판만 검색 결과에 포함/제외
- **빠른 검색**: 효율적인 UNION 쿼리로 빠른 검색

**사용 방법:**
- 헤더 메뉴 → "🔍 통합검색" 클릭
- 검색어 입력 후 검색

**게시판 내 검색:**
- 제목, 내용, 작성자별 검색 필터
- 각 게시판 목록 페이지에서 사용 가능

### ⚙️ 에디터 설정 (Editor Toggle)
게시판별로 에디터 사용 여부를 선택할 수 있습니다.

- **WYSIWYG 에디터**: Summernote 리치 텍스트 에디터
- **일반 텍스트**: 간단한 textarea 입력
- **게시판별 설정**: 각 게시판마다 독립적으로 설정

**설정 방법:**
1. 관리자 페이지 → 게시판 관리
2. 게시판 편집 → "에디터 사용" 체크박스

### 📊 SEO 및 분석 도구 (SEO & Analytics)
검색엔진 최적화와 분석 도구를 간편하게 설정할 수 있습니다.

**지원 도구:**
- ✅ Bing Webmaster Tools
- ✅ Google Search Console
- ✅ Google Analytics (GA4)
- ✅ Google Tag Manager
- ✅ Google AdSense
- ✅ 커스텀 헤더/푸터 스크립트

**설정 방법:**
1. 관리자 페이지 → SEO 설정
2. 각 도구의 인증 코드 또는 ID 입력
3. 저장하면 자동으로 모든 페이지에 적용

**특징:**
- 자동 스크립트 삽입
- 메타 태그 자동 생성
- GTM Head & Body 모두 삽입
- 커스텀 스크립트 지원

---

## 📦 데이터베이스 업데이트

새로운 기능을 사용하려면 다음 스크립트를 실행하세요:

```bash
# 권한 시스템
http://your-domain/update_db_permissions.php

# 에디터 설정
http://your-domain/update_db_editor.php

# 통합검색
http://your-domain/update_db_search.php

# SEO 설정
http://your-domain/update_db_seo.php
```

또는 관리자 페이지 → 게시판 관리에 접속하면 자동으로 업데이트됩니다.

---

## 🚀 빠른 시작 (Quick Start)

### 1. 다운로드 및 설치
```bash
git clone https://github.com/mytajimilife-coder/microboard.git
cd microboard
```

### 2. 데이터베이스 설정
- `config.php`에서 데이터베이스 정보 입력
- `http://your-domain/install.php` 접속하여 설치

### 3. 관리자 로그인
- ID: admin
- PW: admin (설치 후 즉시 변경 권장)

### 4. 기능 설정
- 관리자 페이지에서 각종 기능 설정
- SEO 도구 연동
- OAuth 소셜 로그인 설정

---

## 📖 문서 (Documentation)

- [한국어 기능 가이드](FEATURES.md)
- [English Features Guide](FEATURES_EN.md)
- [日本語機能ガイド](FEATURES_JA.md)
- [中文功能指南](FEATURES_ZH.md)
- [OAuth 설정 가이드](OAUTH_SETUP.md)
- [보안 정책](SECURITY.md)

---

## 🔗 링크 (Links)

- **GitHub**: https://github.com/mytajimilife-coder/microboard
- **GitHub Pages**: https://mytajimilife-coder.github.io/microboard/
- **Issue 리포트**: https://github.com/mytajimilife-coder/microboard/issues
- **Discussions**: https://github.com/mytajimilife-coder/microboard/discussions

---

## 📝 라이선스 (License)

MIT License - 자유롭게 사용, 수정, 배포 가능합니다.

---

**MicroBoard v1.0.0** - Made with ❤️ by the MicroBoard Team
