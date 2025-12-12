import axios from "axios";

const appUrl = import.meta.env.VITE_APP_URL
    ? `${import.meta.env.VITE_APP_URL}/api`
    : "http://localhost:8000/api";

// Create an Axios instance
const axiosInstance = axios.create({
    baseURL: appUrl, // Change to your API base URL
    headers: {
        "X-Requested-With": "XMLHttpRequest",
    },
});

// Add a request interceptor
axiosInstance.interceptors.request.use(
    (config) => {
        // Example: Add an Authorization header if needed
        const token =
            typeof window !== "undefined"
                ? localStorage.getItem("token")
                : null;

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// // Add a response interceptor
axiosInstance.interceptors.response.use(
    (response) => response,
    (error) => {
        // Handle errors globally if needed
        if (error.response && error.response.status === 401) {
            console.error("Unauthorized! Redirecting to login...");
        }
        return Promise.reject(error);
    }
);

export default axiosInstance;
