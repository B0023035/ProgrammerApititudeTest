export const testAccounts = {
    user: {
        email: process.env.TEST_USER_EMAIL || "B0023035@ib.yic.ac.jp",
        password: process.env.TEST_USER_PASSWORD || "password",
        name: "テストユーザー",
    },
    admin: {
        email: process.env.TEST_ADMIN_EMAIL || "a@a",
        password: process.env.TEST_ADMIN_PASSWORD || "Passw0rd",
        name: "管理者",
    },
    guest: {
        school: "YIC高等学校",
        name: "山口太郎",
    },
    sessionCode: process.env.TEST_SESSION_CODE || "TEST-CODE-123",
};

export const testUrls = {
    baseUrl: process.env.TEST_BASE_URL || "http://localhost:8000",
    login: "/login",
    adminLogin: "/admin/login",
    sessionEntry: "/",
    welcome: "/welcome",
    testStart: "/test-start",
    guestInfo: "/guest/info",
    practice: (section: number) => `/practice/${section}`,
    exam: (part: number) => `/exam/part/${part}`,
};
