// // Passive event listener support to prevent scroll-blocking warnings
// (function() {
//   let supportsPassive = false;
//   try {
//     const opts = Object.defineProperty({}, 'passive', {
//       get: function() {
//         supportsPassive = true;
//       }
//     });
//     window.addEventListener('testPassive', null, opts);
//     window.removeEventListener('testPassive', null, opts);
//   } catch (e) {}

//   // Override addEventListener to make touch and wheel events passive by default
//   if (supportsPassive) {
//     const originalAddEventListener = EventTarget.prototype.addEventListener;
//     EventTarget.prototype.addEventListener = function(type, listener, options) {
//       const usesPassive = ['touchstart', 'touchmove', 'wheel', 'mousewheel'].includes(type);
//       if (usesPassive && typeof options !== 'object') {
//         options = { passive: true };
//       } else if (usesPassive && typeof options === 'object' && !('passive' in options)) {
//         options.passive = true;
//       }
//       return originalAddEventListener.call(this, type, listener, options);
//     };
//   }
// })();

// Make coursePlayer function globally available
function coursePlayer() {
  return {
    courseData: null,
    currentVideo: null,
    sidebarOpen: false,
    isDark: false,
    currentVideoIndex: 0,
    allVideos: [],
    
    init() {
      this.loadCourseData();
      this.initializeTheme();
      this.setupResizeHandler();
    },
    
    async loadCourseData() {
      try {
        // Use the API URL passed from WordPress if available, otherwise fallback to course.json
        const apiUrl = icLmsConfig.apiUrl;
        const response = await fetch(apiUrl);
        this.courseData = await response.json();
        
        // Flatten all videos for navigation
        this.allVideos = [];
        this.courseData.course.chapters.forEach(chapter => {
          chapter.videos.forEach(video => {
            this.allVideos.push(video);
          });
        });
        
        // Initialize video states
        this.courseData.course.chapters.forEach(chapter => {
          chapter.isOpen = chapter.id === 1; // Open first chapter by default
          chapter.videos.forEach(video => {
            video.isBookmarked = false;
            video.isCompleted = false;
          });
        });
        
        // Try to restore last watched episode for this course
        const lastWatchedVideo = this.getLastWatchedEpisode();
        
        if (lastWatchedVideo) {
          // Find the video in allVideos array
          const videoToSelect = this.allVideos.find(v => 
            v.id === lastWatchedVideo.episodeId && 
            this.findChapterForVideo(v)?.id === lastWatchedVideo.chapterId
          );
          
          if (videoToSelect) {
            this.selectVideo(videoToSelect);
          } else {
            // Fallback to first video if saved video not found
            if (this.allVideos.length > 0) {
              this.selectVideo(this.allVideos[0]);
            }
          }
        } else {
          // Select first video if no saved progress
          if (this.allVideos.length > 0) {
            this.selectVideo(this.allVideos[0]);
          }
        }
        
        // Apply syntax highlighting after content is loaded
        this.$nextTick(() => {
          if (typeof hljs !== 'undefined') {
            hljs.highlightAll();
          }
        });
        
      } catch (error) {
        console.error('Failed to load course data:', error);
      }
    },
    
    selectVideo(video) {
      this.currentVideo = video;
      this.currentVideoIndex = this.allVideos.findIndex(v => v.id === video.id);
      
      // Save this as the last watched episode
      this.saveLastWatchedEpisode(video);
      
      // Open the chapter containing this video
      this.courseData.course.chapters.forEach(chapter => {
        chapter.isOpen = chapter.videos.some(v => v.id === video.id);
      });
      
      // Close sidebar on mobile
      if (this.isMobile()) {
        this.sidebarOpen = false;
      }
      
      // Apply syntax highlighting for new content
      this.$nextTick(() => {
        if (typeof hljs !== 'undefined') {
          hljs.highlightAll();
        }
      });
    },
    
    // Helper method to find which chapter a video belongs to
    findChapterForVideo(video) {
      return this.courseData.course.chapters.find(chapter => 
        chapter.videos.some(v => v.id === video.id)
      );
    },
    
    // Save last watched episode to localStorage
    saveLastWatchedEpisode(video) {
      const courseId = this.courseData.course.id;
      const chapter = this.findChapterForVideo(video);
      
      if (courseId && chapter) {
        const progressData = {
          courseId: courseId,
          chapterId: chapter.id,
          episodeId: video.id,
          episodeTitle: video.title,
          timestamp: new Date().toISOString()
        };
        
        localStorage.setItem(`ic_lms_progress_${courseId}`, JSON.stringify(progressData));
      }
    },
    
    // Get last watched episode from localStorage
    getLastWatchedEpisode() {
      const courseId = this.courseData.course.id;
      
      if (!courseId) return null;
      
      const savedData = localStorage.getItem(`ic_lms_progress_${courseId}`);
      
      if (savedData) {
        try {
          return JSON.parse(savedData);
        } catch (e) {
          console.error('Failed to parse saved progress:', e);
          return null;
        }
      }
      
      return null;
    },
    
    toggleChapter(chapter) {
      // Close all chapters, then open selected one (accordion behavior)
      this.courseData.course.chapters.forEach(ch => {
        ch.isOpen = ch.id === chapter.id ? !ch.isOpen : false;
      });
    },
    
    toggleBookmark(video) {
      if (video) {
        video.isBookmarked = !video.isBookmarked;
      }
    },
    
    toggleComplete() {
      if (this.currentVideo) {
        this.currentVideo.isCompleted = !this.currentVideo.isCompleted;
      }
    },
    
    previousVideo() {
      if (this.hasPreviousVideo()) {
        this.currentVideoIndex--;
        this.selectVideo(this.allVideos[this.currentVideoIndex]);
      }
    },
    
    nextVideo() {
      if (this.hasNextVideo()) {
        this.currentVideoIndex++;
        this.selectVideo(this.allVideos[this.currentVideoIndex]);
      }
    },
    
    hasPreviousVideo() {
      return this.currentVideoIndex > 0;
    },
    
    hasNextVideo() {
      return this.currentVideoIndex < this.allVideos.length - 1;
    },
    
    get progressPercentage() {
      if (this.allVideos.length === 0) return 0;
      const completedVideos = this.allVideos.filter(v => v.isCompleted).length;
      return Math.round((completedVideos / this.allVideos.length) * 100);
    },
    
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    
    closeSidebar() {
      this.sidebarOpen = false;
    },
    
    isMobile() {
      return window.innerWidth <= 768;
    },
    
    initializeTheme() {
      const stored = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.isDark = stored ? stored === 'dark' : prefersDark;
      this.updateTheme();
    },
    
    toggleTheme() {
      this.isDark = !this.isDark;
      this.updateTheme();
    },
    
    updateTheme() {
      if (this.isDark) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
    },
    
    setupResizeHandler() {
      window.addEventListener('resize', () => {
        // Alpine.js will automatically re-evaluate isMobile() when needed
      }, { passive: true });
    }
  }
}