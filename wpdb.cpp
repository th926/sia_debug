#include "wpdb.h"

wpdb::wpdb(std::string in_prefix) {
  prefix = in_prefix;
  posts = prefix.append("posts");
  postmeta = prefix.append("postmeta");
  options = prefix.append("options");
}
wpdb::~wpdb() {}
