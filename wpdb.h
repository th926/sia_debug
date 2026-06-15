#include <string>

class wpdb {
    public: std::string posts;
    public: std::string postmeta;
    public: std::string options;
    public: std::string prefix;

    wpdb(std::string in_prefix);
    ~wpdb();
};
