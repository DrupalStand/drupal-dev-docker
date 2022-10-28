{
  description = "DrupalStand";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils }: 
    flake-utils.lib.eachDefaultSystem (system:
      let pkgs = nixpkgs.legacyPackages.${system}; in
      rec {
        devShells = flake-utils.lib.flattenTree {
          default = pkgs.mkShell {
            name = "DrupalStand shell";
            packages = with pkgs; [
              docker
              docker-compose
              gnumake
              # Used only for scripts/docker-compose-wait.py
              python3Minimal
            ];
          };
        };

      }
    );
}
